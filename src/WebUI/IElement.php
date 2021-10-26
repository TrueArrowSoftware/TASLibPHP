<?php
namespace TAS\Core\WebUI;

/**
 * Interface for All new Element Render.
 */
interface IElement {
    /**
     * Render Final HTML
     *
     * @return string
     */
    public function Render(): string; 
}
