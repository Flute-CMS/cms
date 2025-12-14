<?php

namespace Flute\Core\Support\Htmx;

use Flute\Core\Support\Htmx\Response\HtmxClientRedirectResponse;
use Flute\Core\Support\Htmx\Response\HtmxClientRefreshResponse;
use Flute\Core\Support\Htmx\Response\HtmxResponse;
use Flute\Core\Support\Htmx\Response\HtmxStopPollingResponse;
use LogicException;

trait HtmxControllerTrait
{
    /**
     * Issue a htmx client redirect response
     */
    protected function htmxRedirect(string $url): HtmxClientRedirectResponse
    {
        return new HtmxClientRedirectResponse($url);
    }

    /**
     * Issue a htmx client refresh response
     */
    protected function htmxRefresh(): HtmxClientRefreshResponse
    {
        return new HtmxClientRefreshResponse();
    }

    /**
     * Issue a htmx response with the content of a rendered template
     */
    protected function htmxRender(string $view, array $parameters = [], ?HtmxResponse $response = null): HtmxResponse
    {
        return parent::render($view, $parameters, $response ?? new HtmxResponse());
    }

    /**
     * Issue a htmx response with the content of a rendered template block
     */
    protected function htmxRenderBlock(string $view, string $block, array $parameters = [], ?HtmxResponse $response = null): HtmxResponse
    {
        $response ??= new HtmxResponse();
        if (method_exists($this, 'renderBlock')) {
            return parent::renderBlock($view, $block, $parameters, $response);
        }
        if (!$this->container->has('twig')) {
            throw new LogicException('You cannot use the "renderBlock" method if the Twig Bundle is not available. Try running "composer require symfony/twig-bundle".');
        }
        $content = $this->container->get('twig')->load($view)->renderBlock($block, $parameters);
        $response->setContent($content);

        return $response;
    }

    /**
     * Issue a htmx stop polling response
     */
    protected function htmxStopPolling(): HtmxStopPollingResponse
    {
        return new HtmxStopPollingResponse();
    }
}
