<?php

namespace NEOSidekick\UiReverseSearch\FlowQueryOperations;

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Neos\Routing\FrontendNodeRoutePartHandlerInterface;

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
     * @Flow\InjectConfiguration(package="Neos.Flow", path="mvc.routes")
     * @var array
     */
    protected array $routesConfiguration;

    /**
     * @Flow\InjectConfiguration(path="overrideNodeUriPathSuffix")
     * @var string|null
     */
    protected ?string $overrideNodeUriPathSuffix;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

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

        /** @var NodeInterface $contextNode */
        $contextNode = $flowQuery->getContext()[0];
        $context = $contextNode->getContext();

        $matchingNodeFromPublicUri = $this->tryToResolvePublicUriToNode($term, $filterNodeTypeName, $context);
        if ($matchingNodeFromPublicUri) {
            $flowQuery->setContext([$matchingNodeFromPublicUri]);
            return;
        }

        $matchingNodeFromNodePath = $this->tryToResolveNodePathToNode($term, $filterNodeTypeName, $context);
        if ($matchingNodeFromNodePath) {
            $flowQuery->setContext([$matchingNodeFromNodePath]);
            return;
        }

        // If nothing can be found, fall back to the original implementation
        parent::evaluate($flowQuery, $arguments);
    }

    protected function tryToResolvePublicUriToNode(mixed $term, mixed $filterNodeTypeName, Context $context): ?NodeInterface
    {
        if (!preg_match('/(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s]{2,}|www\.[a-zA-Z0-9]+\.[^\s]{2,})/', $term)) {
            return null;
        }

        $uri = new Uri($term);
        // Remove the starting slash.
        $path = str_starts_with($uri->getPath(), '/') ? substr($uri->getPath(), 1) : $uri->getPath();

        $routeHandler = $this->objectManager->get(FrontendNodeRoutePartHandlerInterface::class);
        $routeHandler->setName('node');

        $uriPathSuffix = !empty($this->overrideNodeUriPathSuffix) ? $this->overrideNodeUriPathSuffix : $this->routesConfiguration['Neos.Neos']['variables']['defaultUriSuffix'];
        $routeHandler->setOptions(['uriPathSuffix' => $uriPathSuffix, 'nodeType' => $filterNodeTypeName]);

        $routeParameters = RouteParameters::createEmpty();
        // This is needed for the FrontendNodeRoutePartHandler to correctly identify the current site
        $routeParameters = $routeParameters->withParameter('requestUriHost', $uri->getHost());
        $matchResult = $routeHandler->matchWithParameters($path, $routeParameters);

        if (!$matchResult || !$matchResult->getMatchedValue()) {
            return null;
        }

        $nodeContextPath = $matchResult->getMatchedValue();
        $nodePath = NodePaths::explodeContextPath($nodeContextPath)['nodePath'];
        $matchingNode = $context->getNode($nodePath);

        if (!$matchingNode) {
            return null;
        }

        return $matchingNode;
    }

    protected function tryToResolveNodePathToNode(string $term, string $filterNodeTypeName, Context $context): ?NodeInterface
    {
        if (!str_starts_with($term, '/sites')) {
            return null;
        }

        // We only need the node path, so we split the context path from the search term
        $nodePath = explode('@', $term)[0];
        if (!preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $nodePath)) {
            return null;
        }

        $matchingNode = $context->getNode($nodePath);
        if (!$matchingNode || !$matchingNode->getNodeType()->isOfType($filterNodeTypeName)) {
            return null;
        }

        return $matchingNode;
    }
}
