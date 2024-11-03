# Search

The standard WordPress search does not index content within Advanced Custom Fields https://www.advancedcustomfields.com/.

As components are created in a modular approach using the page builder powered by ACF flexible content and have ACF at the heart of how the site allows editors to have so much control, the standard WordPress search is not sufficient.

## Search WP
Search WP - https://searchwp.com/ predominantly gives us the ability to:
  - Search content within ACF fields
  - Search assets such as PDFs
  - Weight the content so that certain matches are of higher value. FOR E.G. A match in Hero title is a better indicator of the relevance of the page than a match in a signposting module.

### Search WP Version 4
The main bulk of the content and power of a page is within the `page_builder` field. This is a flexible content field from ACF.

#### Versions < 4
In previous versions of Search WP in other projects, we have had to manually specify `meta_keys` to be indexed.

For flexible content and repeater fields, we require the use of wildcards when specifying the fields to index. This is because a component may appear in any position within the flexible content array and we need to match wherever it is used.

Example from https://searchwp.com/search-acf-advanced-custom-fields-data-in-wordpress/ below:

```php
<?php

function my_searchwp_acf_repeater_keys( $keys ) {
	$keys[] = 'staff_%'; // will match all ACF Repeater fields in the Staff repeater

	return $keys;
}

add_filter( 'searchwp_custom_field_keys', 'my_searchwp_acf_repeater_keys' );
```

A rule for each component within the page builder would have to be created so that it would appear on the SearchWP search engine screen and then could be weighted.

#### Version 4
The `searchwp_custom_field_keys` hook has been completely removed. It's now possible to search and add ACF fields in the Search WP search engine screen.


The above format using a `_%_` wildcard does not seem to work. However, using a `*` instead does.

**Example**
We are adding and registering a statistic module to be indexed `wp-admin/options-general.php?page=searchwp`.

The statistic module sits withing the page builder flexible content field that has the key `page_modules`.

The statistic module key is `statistic`. 

1)  Click add `add/remove attributes`.
2) Type in the custom fields box `page_modules*statistic`
(make sure to press enter)
3) Click done
4) Check the attribute has been added. 
5) Save Search Engine and rebuild the index
6) Test the search results

A catch all is therefore also possible. `page_modules*`.


**Issues**
The Search WP UI does not play well with long key references. In the example above we could want to match more specifically `page_modules*statistic*statistic_heading`. To avoid issues with the UI try to keep keys as succinct as they can be. Page modules used to be named `acf_field_page_modules` but were simplified partly for this reason.
