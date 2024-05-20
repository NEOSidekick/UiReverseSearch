<?php

namespace NEOSidekick\UiReverseSearch\FlowQueryOperations;

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Neos\Routing\FrontendNodeRoutePartHandler;

class SearchOperation extends \Neos\Neos\Ui\FlowQueryOperations\SearchOperation
{
    /**
     * We have to have a higher priority here
     * than the original implementation to
     * override it (original has 100).
     *
     * @var int
     */
    protected static $priority = 200;

    /**
     * This method is inspired from and extends its original implementation
     * by first checking if the search term is a URI. If not, we pass
     * further to the original implementation.
     *
     * If that is the case, we use the FrontendNodeRoutePartHandler
     * to look up a matching node and additionally test
     * if it also matches the node type filter.
     *
     * @inheritDoc
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        $term = $arguments[0] ?? null;
        $filterNodeTypeName = $arguments[1] ?? null;
        $filterNodeTypes = strlen($filterNodeTypeName) > 0 ? [$filterNodeTypeName] : array_keys($this->nodeTypeManager->getSubNodeTypes('Neos.Neos:Document', false));

        /** @var NodeInterface $contextNode */
        $contextNode = $flowQuery->getContext()[0];
        $context = $contextNode->getContext();

        if (!preg_match('/(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s]{2,}|www\.[a-zA-Z0-9]+\.[^\s]{2,})/', $term)) {
            parent::evaluate($flowQuery, $arguments);
            return;
        }

        $uri = new Uri($term);
        // Remove the starting slash.
        $path = str_starts_with($uri->getPath(), '/') ? substr($uri->getPath(), 1) : $uri->getPath();

        $routeHandler = new FrontendNodeRoutePartHandler();
        $routeHandler->setName('node');
        $routeParameters = RouteParameters::createEmpty();
        // This is needed for the FrontendNodeRoutePartHandler to correctly identify the current site
        $routeParameters->withParameter('requestUriHost', $uri->getHost());
        $matchResult = $routeHandler->matchWithParameters($path, $routeParameters);

        if (!$matchResult || !$matchResult->getMatchedValue()) {
            $flowQuery->setContext([]);
            return;
        }

        $nodeContextPath = $matchResult->getMatchedValue();
        $nodePath = NodePaths::explodeContextPath($nodeContextPath)['nodePath'];
        $matchingNode = $context->getNode($nodePath);

        if (!$matchingNode) {
            $flowQuery->setContext([]);
            return;
        }

        $matchingNodeIsOfNodeType = array_reduce($filterNodeTypes, function (bool $carry, string $nodeType) use ($matchingNode) {
            return $carry || $matchingNode->getNodeType()->isOfType($nodeType);
        }, false);

        if (!$matchingNodeIsOfNodeType) {
            $flowQuery->setContext([]);
            return;
        }

        $flowQuery->setContext([$matchingNode]);
    }
}
