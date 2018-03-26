Code for TypeTango.com

Every page resolves to a directory in the `/pages` directory (see `.htaccess`). For example: `/search` resolves to `pages/search/index.php`

A typical `index.php` file does the following:

1. Include `/includes/config.php`, which sets up custom error handlers, the class autoloader, etc. Custom error handlers are in `/includes/debug`.
2. Instantiate a PageShell from `/includes/shells`. This starts output buffering. When the script ends, the PageShell destructor prints the page's output between the appropriate html tags (if any).
3. Include the page's JavaScript and CSS files. Those files serve as the page's view. JavaScript prints all page-specific code (non-specific markup is printed by the PageShells).
4. Include any data which the JavaScript file will need in order to print the page interface (such as form values).
   This means interacting with the model library: `/includes/models` and `/includes/finders`
5. JavaScript page files typically use AJAX calls to interact with their own page-specific `ajax.php` files.
   They use hash fragment navigation where appropriate.

`/includes/facade` contains classes such as `DB`, `Session`, and `Email` that serve as facades.

