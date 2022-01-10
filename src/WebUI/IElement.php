<?php

namespace TAS\Core\WebUI;

/**
 * Interface for All new Element Render.
 */
interface IElement
{
    /**
     * Render Final HTML.
     */
    public function Render(): string;
}
