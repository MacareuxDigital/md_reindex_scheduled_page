# Reindex Scheduled Page Task

A Concrete CMS package to add a task to reindex pages that has not indexed scheduled version.

Concrete CMS has a feature to schedule a page to be published at a future date.
When the page is scheduled, a new version of the page is created and is set to be published at the scheduled date.
However, the search index is not updated until the page is updated or run the reindex task.
This issue is reported in the Concrete CMS GitHub issue tracker: https://github.com/concretecms/concretecms/issues/11844

This package adds a task to reindex pages that have a scheduled version that has not been indexed.