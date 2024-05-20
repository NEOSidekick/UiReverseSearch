# Neos UI Document Reverse Search by URI

This package extends the document tree search in the Neos UI with an ability to search by the public URI of a document.

# How does it work?

The Neos UI uses FlowQuery and the `Neos\Neos\Ui\FlowQueryOperations\SearchOperation` to filter the document nodes.
Our package extends and overrides this implementation by first checking if the search term matches
the URI structure. If this is the case, it uses `Neos\Neos\Routing\FrontendNodeRoutePartHandler` to
resolve the URI to a node context path.
