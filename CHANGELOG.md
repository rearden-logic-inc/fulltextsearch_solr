# Changelog

## v 0.5.0

- Features
  - Add optimize command for solr

- Fixes
  - Issues with uprefix are not worked out.  Automatically adding nc_ to tags, comments, and any subtags that are in
    the index document. 

## v 0.4.0

- Features
  - Search and index comments from files
  - Add absolute path when possible to index document so file doesn't need to be written out before indexing.
- Fixes
  - Remove unnecessary comments/methods/todos
  - Improve exception messages

## v 0.3.0

- Features
  - Allow Pagination of results
  - Index Reset Functionality in place
  - Update Solarium to 5.0.1
  - Process Tags and SubTags for queries and search requests
 
## v 0.2.0

Bug Fixes 

- Don't upload folders to Solr
- Remove the temporary files after executing the extract query.

## v 0.1.0 

Initial migration of the code from elasticsearch to solr.  
