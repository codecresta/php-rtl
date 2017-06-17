PHP RTL API
-----------

This file gives a summary of the main features of RTL.

PHP RTL (REST and Twig layer) API is an API / layer for interfacing with a REST
service and working in conjunction with Twig (Symfony) templates.  It was
developed to work with Django REST Framework but could be used to interface with
any REST API via CURL.  The REST service is assumed to have authentication /
sessions.

The following high level functions are available for managing / rendering
particular pages or scripts corresponding to the various HTTP methods:
- get page, for displaying a single record (HTTP GET)
- list page, for displaying multiple records (HTTP GET)
- edit page (HTTP POST / PATCH)
- patch script (HTTP PATCH)
- delete script (HTTP DELETE)

When a PHP script interfaces with one REST API function it is customary to use
the same name for the PHP script and the end point via the getBaseName function,
for example if the script was called "foo.php":
renderListPage(getBaseName());
The corresponding Twig template would be "foo.twig".

It is possible to create a custom page through the RTL that could interface with
say two REST end points using two Layer class instances.  Look at the render
functions in "lib.php" an example of how to use the Layer class!  The Layer
class allows fields to be registered for interfacing with a REST API end point.

So to use RTL a PHP script is required either on its own, for example
using deleteNext, or in conjunction with a Twig template, for example using
"renderGetPageId".

It is neccessary to have a template called "api-error.twig" which has the
elements "error_message", "error_text" and "error_code" in the Twig variables
array.

There are various functions in RTL for managing the variables array used
to pass data to a Twig template.  There are also functions for managing PHP
session variables in relation to the variables array.

Here is an example PHP script called profile.php that uses RTL:
<?php
	require "lib.php";
	$action = renderEditPage(getBaseName(), ["about", "dt_of_birth", "gender",
        "format"], settings(["PROFILE_ABOUT_ROWS", "PROFILE_ABOUT_LENGTH",
        "GENDER_MALE", "GENDER_FEMALE"]));
	if (in_array($action, ["post", "patch"])) {
		$layer = new Layer();
		if ($layer->get("user-profile"))
			$layer->setFieldSession("format");
	}
	if (!empty($action))
		directTo("launchpad");
?>

In this example it is assumed that the settings in upper case are defined in
"consts.php" in the "Setting" class.  The fields "about, dt_of_birth, ..." etc
should have corresponding variables in the "profile" Twig template.  Here if the
profile is saved by the user then a value for the field "format" is retrieved
from the REST API and used to set a PHP session variable of the same name.  The
user is then directed to the page "launchpad.php".

Code Cresta, 2017
http://www.codecresta.com
