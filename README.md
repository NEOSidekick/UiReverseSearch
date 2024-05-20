# Neos UI Document Reverse Search by URI

This package extends the document tree search in the Neos UI with an ability to search by the public URI of a document.
Just copy the public URI from your browser into the search field and the document will be highlighted in the document tree.

![Demo](https://github.com/NEOSidekick/UiReverseSearch/assets/4405087/6127e7c2-e363-4185-bffa-1f8d9c7c6f80)


# How does it work?

The Neos UI uses FlowQuery and the `Neos\Neos\Ui\FlowQueryOperations\SearchOperation` to filter the document nodes.
Our package extends and overrides this implementation by first checking if the search term matches
the URI structure. If this is the case, it uses `Neos\Neos\Routing\FrontendNodeRoutePartHandler` to
resolve the URI to a node context path.
