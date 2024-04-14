<?php

namespace Sitegeist\EditorWidgets\Traits;

use Psr\Http\Message\ServerRequestInterface;

trait RequestAwareTrait
{
    private ServerRequestInterface $request;

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }
}
