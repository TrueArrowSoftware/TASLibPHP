<?php

namespace TAS\Core;

/**
 * Seek ("keyset") paging for Grid: an anchor row plus a short skip instead of a deep OFFSET.
 *
 * Page links keep page=N, and add cur=<token> holding the first/last row sort values of the page
 * being viewed. Page N is then read as "rows after/before that anchor, skipping a few pages",
 * which reads at most ~2 pager windows of rows however deep the page is. No (or stale) token
 * simply falls back to a plain OFFSET query, so every URL still returns the right page.
 *
 * Contract for sort columns (Grid field definition 'seek' => 'sql expression' | true):
 *  - the expression must be usable in WHERE (select aliases are not), and never NULL
 *    ponytail: NULL-able sort columns are not seekable (they fall back to offset), add NULL-aware compare if needed.
 *  - row[field] must hold the raw value of that expression (not a formatted copy).
 * The grid's index field is always the last, unique, tiebreaker.
 */
class KeysetPager
{
    private const LINK_WINDOW = 10;
    private const MAX_TOKEN_LENGTH = 1500;

    /** @var array<int, array{field: string, expr: string, dir: string}> */
    private array $cols;

    private function __construct(array $cols)
    {
        $this->cols = $cols;
    }

    /**
     * Returns null when a sort column is not seekable, the caller then pages by offset.
     *
     * @param array $extra QueryOptions['orderby'] entries like 'name asc'
     * @param array $fields Grid fields, a field is seekable with 'seek' => 'sql expression' (or true to use its name)
     */
    public static function Build(string $orderby, string $direction, array $extra, array $fields, string $indexField, string $indexExpr = ''): ?self
    {
        if ('' === $orderby || '' === $indexField) {
            return null;
        }
        $indexExpr = '' !== $indexExpr ? $indexExpr : $indexField;

        $cols = [];
        $add = function (string $name, string $dir) use (&$cols, $fields, $indexField, $indexExpr): bool {
            foreach ($cols as $c) {
                if ($c['field'] === $name) {
                    return true;
                }
            }
            $seek = is_array($fields[$name] ?? null) ? ($fields[$name]['seek'] ?? null) : null;
            if (is_string($seek) && '' !== trim($seek)) {
                $expr = $seek;
            } elseif (true === $seek) {
                $expr = $name;
            } elseif ($name === $indexField) {
                $expr = $indexExpr;
            } else {
                return false;
            }
            $cols[] = ['field' => $name, 'expr' => $expr, 'dir' => ('desc' === strtolower($dir)) ? 'desc' : 'asc'];

            return true;
        };

        if (!$add($orderby, $direction)) {
            return null;
        }
        foreach ($extra as $val) {
            $parts = preg_split('/\s+/', trim((string) $val));
            if ('' !== $parts[0] && !$add($parts[0], $parts[1] ?? 'asc')) {
                return null;
            }
        }
        $add($indexField, 'asc');

        return new self($cols);
    }

    /**
     * Order by list (without the "order by" keyword). Reversed is used to read backwards from an anchor.
     */
    public function OrderBy(bool $reverse = false): string
    {
        $parts = [];
        foreach ($this->cols as $c) {
            $dir = $c['dir'];
            if ($reverse) {
                $dir = ('asc' === $dir) ? 'desc' : 'asc';
            }
            $parts[] = $c['expr'] . ' ' . $dir;
        }

        return implode(', ', $parts);
    }

    /**
     * Condition for the rows after (forward) or before (backward) the anchor values, in sort order.
     * Expanded as (a > x) or (a = x and b > y) ... so every column can have its own direction.
     *
     * @param array<int, string> $values anchor value per sort column
     * @param bool $inclusive include the anchor row itself
     * @param callable $escape string escaper, DB::Escape
     */
    public function Seek(array $values, bool $forward, bool $inclusive, callable $escape): string
    {
        $last = count($this->cols) - 1;
        $or = [];
        $eq = [];
        $quoted = [];
        foreach ($this->cols as $i => $c) {
            $quoted[$i] = "'" . $escape((string) $values[$i]) . "'";
            $op = ((('asc' === $c['dir']) === $forward) ? '>' : '<') . (($inclusive && $i === $last) ? '=' : '');
            $or[] = '(' . implode(' and ', array_merge($eq, [$c['expr'] . " {$op} " . $quoted[$i]])) . ')';
            $eq[] = $c['expr'] . ' = ' . $quoted[$i];
        }
        $sql = implode(' or ', $or);
        if ($last > 0) {
            // Redundant range on the leading column, lets MySQL start the index range scan there.
            $lead = (('asc' === $this->cols[0]['dir']) === $forward) ? '>=' : '<=';
            $sql = $this->cols[0]['expr'] . " {$lead} " . $quoted[0] . " and ({$sql})";
        }

        return $sql;
    }

    /**
     * The grid's own where with the seek condition added. The where is wrapped in parentheses, otherwise
     * "where a or b" would become "a or (b and seek)" and the seek condition would only apply to part of it.
     *
     * @param string $where blank, or a plain " where ..." clause
     */
    public static function Where(string $where, string $condition): string
    {
        if ('' === $condition) {
            return $where;
        }
        $rest = preg_replace('/^\s*where\b/i', '', $where);

        return ('' === trim($rest)) ? " where ({$condition}) " : " where ({$rest}) and ({$condition}) ";
    }

    /**
     * Ties a cursor to the sort and filter it was made for, a cursor of another one is ignored.
     */
    public function Hash(string $salt): string
    {
        return substr(md5($this->OrderBy() . '|' . $salt), 0, 8);
    }

    /**
     * Cursor token for the rows shown on $page, or null when it can't be made (NULL value, too long).
     */
    public function Encode(int $page, array $firstRow, array $lastRow, string $hash): ?string
    {
        $f = [];
        $l = [];
        foreach ($this->cols as $c) {
            $a = $firstRow[$c['field']] ?? null;
            $b = $lastRow[$c['field']] ?? null;
            if (!is_scalar($a) || !is_scalar($b)) {
                return null;
            }
            $f[] = (string) $a;
            $l[] = (string) $b;
        }
        $json = json_encode(['p' => $page, 'h' => $hash, 'f' => $f, 'l' => $l]);
        if (false === $json) {
            return null;
        }
        $token = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');

        return (strlen($token) > self::MAX_TOKEN_LENGTH) ? null : $token;
    }

    /**
     * @return array{p: int, h: string, f: array<int, string>, l: array<int, string>}|null null when missing, damaged or made for another sort/filter
     */
    public function Decode(string $token, string $hash): ?array
    {
        // The token comes from the URL: bound its size before decoding (Encode never makes one longer).
        if ('' === $token || strlen($token) > self::MAX_TOKEN_LENGTH) {
            return null;
        }
        $json = base64_decode(strtr($token, '-_', '+/'), true);
        $d = (false === $json) ? null : json_decode($json, true, 4);
        if (!is_array($d) || ($d['h'] ?? null) !== $hash || !is_int($d['p'] ?? null) || $d['p'] < 1) {
            return null;
        }
        foreach (['f', 'l'] as $k) {
            if (!is_array($d[$k] ?? null) || count($d[$k]) !== count($this->cols)) {
                return null;
            }
            foreach ($d[$k] as $v) {
                if (!is_string($v)) {
                    return null;
                }
            }
            $d[$k] = array_values($d[$k]);
        }

        return $d;
    }

    /**
     * How to read $page.
     * mode offset = plain skip, after/before = seek from the cursor's last/first row then skip,
     * last = read backwards from the end (pass $total only when the last page is wanted).
     *
     * @return array{mode: string, values: array<int, string>, inclusive: bool, skip: int, limit: int}
     */
    public function Plan(int $page, int $pagesize, ?array $cursor, ?int $total = null): array
    {
        if (null !== $total && $total > $pagesize) {
            return ['mode' => 'last', 'values' => [], 'inclusive' => false, 'skip' => 0, 'limit' => (($total - 1) % $pagesize) + 1];
        }

        $plan = ['mode' => 'offset', 'values' => [], 'inclusive' => false, 'skip' => max(0, ($page - 1) * $pagesize), 'limit' => $pagesize];
        if ($page <= 1 || null === $cursor) {
            return $plan;
        }

        $from = $cursor['p'];
        if ($page === $from) {
            // Same page again (refresh): start at its first row.
            return ['mode' => 'after', 'values' => $cursor['f'], 'inclusive' => true, 'skip' => 0, 'limit' => $pagesize];
        }
        if ($page > $from) {
            return ['mode' => 'after', 'values' => $cursor['l'], 'inclusive' => false, 'skip' => ($page - $from - 1) * $pagesize, 'limit' => $pagesize];
        }

        return ['mode' => 'before', 'values' => $cursor['f'], 'inclusive' => false, 'skip' => ($from - $page - 1) * $pagesize, 'limit' => $pagesize];
    }

    /**
     * Pager bar with the same markup as Utility::Paging, every link carries the cursor.
     * Page 1 needs no cursor and the last page link asks for a read from the end.
     *
     * @param string|null $token cursor of the rows being shown, from Encode()
     * @param string $querystring extra query string for every link
     */
    public static function Links(string $pagename, int $page, int $total, int $pagesize, ?string $token = null, string $querystring = ''): string
    {
        $pagename = Web::AppendQueryString($pagename, $querystring);
        $href = function (int $p, bool $last = false) use ($pagename, $token): string {
            $q = 'page=' . $p;
            if ($last) {
                $q .= '&last=1';
            } elseif ($p > 1 && null !== $token) {
                $q .= '&cur=' . $token;
            }

            return Web::AppendQueryString($pagename, $q);
        };
        // gridurl is sometimes built from the request (PHP_SELF...), so keep it from closing the attribute. & is left alone like the old pager.
        $link = fn (string $url, string $text, string $class = ''): string => '<a href="'
            . str_replace(['"', "'", '<', '>'], ['&quot;', '&#039;', '&lt;', '&gt;'], $url)
            . '" class="ui-state-default ui-corner-all' . $class . '">' . $text . '</a>';

        $bar = [];
        $pages = 1;
        if ($total > $pagesize) {
            $pages = (int) ceil($total / $pagesize);
            if ($page > 1) {
                $bar[] = $link($href(1), '<span class="fa fa-angle-double-left"></span>');
                $bar[] = $link($href($page - 1), '<span class="fa fa-angle-left"></span>');
            }
            $startl = $page - ($page % self::LINK_WINDOW) + 1;
            $endl = min($pages, $startl + self::LINK_WINDOW - 1);
            $nStart = 0;
            if ($page > self::LINK_WINDOW - 1) {
                $nStart = 1;
                $prev = $startl - self::LINK_WINDOW;
                $bar[] = $link($href($prev), (string) $prev) . ' <li>....</li> ';
            }
            for ($i = $startl - $nStart; $i <= $endl; ++$i) {
                $bar[] = ($i === $page) ? $link('#', (string) $i, ' active') : $link($href($i), (string) $i);
            }
            if ($page + 1 <= $pages) {
                $bar[] = $link($href($page + 1), '<span class="fa fa-angle-right"></span>');
                $bar[] = $link($href($pages, true), '<span class="fa fa-angle-double-right"></span>');
            }
        }

        return '<ul><li>' . implode('</li><li>', $bar) . '</li></ul><div class="showcurrentpage">' . $page . ' of ' . $pages . '</div>';
    }
}
