<?php
	class Setting {
		const CLIENT_ID = "client authentication ID goes here";
		const CLIENT_SECRET = "client authentication secret goes here";
		const API_URL = "http://127.0.0.1:6969";
		const API_ERROR_LOG = "../APIErrorLog.txt";
		// Twig and vendor directories must be set up for use with Twig and Composer:
		const VENDOR_DIRECTORY = "vendor";
		const TWIG_COMPILATION_DIRECTORY = "../twig_comp";
		const TEMPLATE_DIRECTORY = "../tmpl";
		const EXPIRE_SECONDS = 60;
		const DT_TM_FORMAT = "Y-m-d H:i:s";
		const COULD_NOT_COMPLETE = "Could not complete request.	 Please ensure you are logged in!";
		const INCORRECT_PARAMETERS = "Incorrect parameters.";
	}
	$http_code = [
		0 => "No Response",
		100 => "Continue",
		101 => "Switching Protocols",
		102 => "Processing",
		200 => "OK",
		201 => "Created",
		202 => "Accepted",
		203 => "Non-Authoritative Information",
		204 => "No Content",
		205 => "Reset Content",
		206 => "Partial Content",
		207 => "Multi-Status",
		300 => "Multiple Choices",
		301 => "Moved Permanently",
		302 => "Found",
		303 => "See Other",
		304 => "Not Modified",
		305 => "Use Proxy",
		306 => "Switch Proxy",
		307 => "Temporary Redirect",
		400 => "Bad Request",
		401 => "Unauthorized",
		402 => "Payment Required",
		403 => "Forbidden",
		404 => "Not Found",
		405 => "Method Not Allowed",
		406 => "Not Acceptable",
		407 => "Proxy Authentication Required",
		408 => "Request Timeout",
		409 => "Conflict",
		410 => "Gone",
		411 => "Length Required",
		412 => "Precondition Failed",
		413 => "Request Entity Too Large",
		414 => "Request-URI Too Long",
		415 => "Unsupported Media Type",
		416 => "Requested Range Not Satisfiable",
		417 => "Expectation Failed",
		418 => "I\'m a teapot",
		422 => "Unprocessable Entity",
		423 => "Locked",
		424 => "Failed Dependency",
		425 => "Unordered Collection",
		426 => "Upgrade Required",
		449 => "Retry With",
		450 => "Blocked by Windows Parental Controls",
		500 => "Internal Server Error",
		501 => "Not Implemented",
		502 => "Bad Gateway",
		503 => "Service Unavailable",
		504 => "Gateway Timeout",
		505 => "HTTP Version Not Supported",
		506 => "Variant Also Negotiates",
		507 => "Insufficient Storage",
		509 => "Bandwidth Limit Exceeded",
		510 => "Not Extended"
	];
	class HTTPStatus {
		const OK = 200;
		const CREATED = 201;
		const NO_CONTENT = 204;
		const BAD_REQUEST = 400;
		const UNAUTHORIZED = 401;
		const NOT_FOUND = 404;
	}
	$setting = new Setting();
?>
