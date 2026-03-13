<?php

namespace TAS\Core\Options;

class EmailOptions
{

    public string $replyTo = '';
    public string $replyName = '';

    public array $cc = [];

    public function GetReplyTo(string $sender)
    {
        if (empty($replyTo)) return $sender;
        if (!empty($replyName)) return $replyName . ' <' . $replyTo . '>';
        return $replyTo;
    }
}
