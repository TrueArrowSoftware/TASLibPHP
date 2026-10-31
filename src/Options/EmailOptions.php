<?php

namespace TAS\Core\Options;

class EmailOptions
{

    public string $replyTo = '';
    public string $replyName = '';

    public array $cc = [];

    public function GetReplyTo(string $sender)
    {
        // the properties: these read undefined locals, so the sender always came back
        if (empty($this->replyTo)) return $sender;
        if (!empty($this->replyName)) return $this->replyName . ' <' . $this->replyTo . '>';
        return $this->replyTo;
    }
}
