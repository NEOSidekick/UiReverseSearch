# Neos UI Document Reverse Search by URI and Node Path

Did you ever ask yourself why you can search for the title in the Neos UI document tree, but not
by public URI or node path :question: Well, we got you :exclamation:

This package extends the document tree search with an ability to search by URI or node path.
Just copy the URI from your browser or the node path into the search field and the
document will be highlighted in the document tree, if found :heart_eyes:

![Demo](https://github.com/NEOSidekick/UiReverseSearch/assets/4405087/6127e7c2-e363-4185-bffa-1f8d9c7c6f80)

## Installation

`NEOSidekick.UiReverseSearch` is available via Packagist. Add `"neosidekick/ui-reverse-search" : "^1.0"` to the require section of the composer.json or run:

```bash
composer require neosidekick/ui-reverse-search
```

We use semantic versioning, so every breaking change will increase the major version number.

## Configuration

Some Neos installations use a suffix to the routes, like `Neos.Demo` uses `.html`. Our package tries to get this information first from
`Neos.Flow.mvc.routes.Neos.Neos.variables.defaultUriSuffix`. If the value in this configuration is not the same as the one configured in your
`Routes.yaml`, you can use our setting `NEOSidekick.UiReverseSearch.overrideNodeUriPathSuffix` to override the uri suffix for the resolving
in this package.

## How does it work?

The Neos UI uses FlowQuery and the `Neos\Neos\Ui\FlowQueryOperations\SearchOperation` to filter the document nodes.
Our package extends and overrides this implementation by first checking if the search term matches
the URI structure. If this is the case, it uses `Neos\Neos\Routing\FrontendNodeRoutePartHandler` to
resolve the URI to a node context path. The resolving for the node path works quite similar, except for that
we don't have to first resolve the URI to a node context path beforehand but we directly look up the node
by the given node path.

## Known limitations

When resolving the node by its public URI, we discard the information about the dimensions and only work with the current context of the UI.
So if your UI is in the German language dimension, and you paste the public URI of a document in the Englisch language dimension,
our package will find its sibling in the German language dimension, if existent.
