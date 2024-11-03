# Filtering
User filtering was required for various post-types with different filters, including, taxonomy, author, and potentially search in the future.

## Requirements
The ability for the user to filter a list of content within a page. Ideally without a page-reload for the best UX.

- Insights - posts filterable by:
  - Topic (custom taxonomy)
  - Author (wp author)
  - Article Type (custom taxonomy)
- People - Custom Post type filterable by:
  - Department - (custom taxonomy)
- Projects - Custom post type filterable by:
  -  Topic (custom taxonomy)
  -  Location (custom taxonomy)


## Filtering flow
In `filters.php` a `get_listings_posts` function does all the logic required for filtering. It accepts an array of key value pairs for filters and `Post types` to apply and return.

```php
$filters = array("topic" => 'Topic', "article-type" => "Article Type", "author" => 'Author');

$context = get_listings_posts($filters, array('post'));

```

### Page load
For each of the three listings (insights, people, and projects), we create a page template. 

By passing the relevant filters and post types as shown above. This adds the listings to the twig context and we can render the template as normal. `listing.twig` that extends `base.twig`.

### User selects filter
When the user saves a filter selection, we intercept the form submission in `ListFilters.js`. 

**JS**
1). Transform the values submitted into a query string
2). Post the values, the current post id and the updated query string to `return_listings_ajax` function within `filters.php` using WP Ajax

**PHP**
3) Pass sent values to `get_listings_posts` to retrieve updated listings.
4) Use sent Post ID to retrieve and add to `$context`
5) Add the query string to context
6) Render `partials/listings-loop.twig` to return markup

**JS**
7) Update the URL with a query string.
8) Use returned markup to update the DOM

### Pagination
Using the default WordPress pagination `page/2/` for example, interferes with the use of custom filters. 

To avoid this, custom pagination is used as a query string param `cPage=2`.

`get_filtered_pagination_link` in `filters.php` handles the pagination link to display to the user.

##### Pagination Challenges
When the twig has been rendered via a filter update (through JS updating markup). When we call `get_filtered_pagination_link` in `pagination.twig`. The PHP function hasn't got a certain WP Context due to it being initially rendered from JS.

WP functions E.G. `get_pagenum_link` will not return the expected current page without the default pagination. But will instead return an address that will include `wp-admin/admin-ajax`. This also means that `home_url( $wp->request )` cannot be used to retrieve the current query string params.

To solve this, we pass down in context the current query string and can use that to build the correct pagination link.


:bulb: If there is a solution to set the WordPress context using the `post.id` then this can be refactored as that is already accessible.

SA 7th Jan 2021 - we could also consider passing down the pagination details with the ajax request (would mean also keeping track of when pages clicked in ListFilter.js.  Example stackoverflow here  https://stackoverflow.com/questions/31887889/wordpress-ajax-admin-ajax-php-pagination)