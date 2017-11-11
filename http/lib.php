<?php
	/*
	RTL PHP API
	Software Author: Daniel W. Grace
	*/
	require "lib-sub.php";
	require Setting::VENDOR_DIRECTORY."/autoload.php";
	Twig_Autoloader::register();
	$memorise;
	function getScriptName() {
		return $_SERVER["SCRIPT_NAME"];
	}
	function getBaseName() {
		return basename(getScriptName(), ".php");
	}
	function getIPAddress() {
		return $_SERVER['REMOTE_ADDR'];
	}
	function getURL($end_point) {
		return Setting::API_URL."/".$end_point;
	}
	function escapeString($string) {
		return htmlspecialchars($string, ENT_QUOTES, "UTF-8");
	}
	function formPost() {
		return $_SERVER["REQUEST_METHOD"] == "POST";
	}
	function post($element_name) {
		if (isset($_POST[$element_name]))
			return $_POST[$element_name];
		else
			return "";
	}
	function formSubmit() {
		return post("button") == "Submit";
	}
	function processInput($string) {
		return stripslashes(trim($string));
	}
	function get($element_name) {
		return escapeString($_GET[$element_name]);
	}
	function isGet($element_name) {
		return isset($_GET[$element_name]);
	}
	function filter($type, $value) {
		switch($type) {
			case "str":
				return true;
			case "bool":
				return in_array($value, ["true", "false"]);
			case "int":
				return filter_var($value, FILTER_VALIDATE_INT) !== false;
			case "float":
				return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
			default:
				return false;
		}		
	}
	function subroutineGet($element_name, $type, &$variable) {
		if (isGet($element_name)) {
			$value = get($element_name);
			$result = filter($type, $value);
			if ($result)
				$variable = $value;
			return $result;
		}
		return false;
	}
	function subroutineGets($element_names, $types_array, &$variables_array) {
		$result = true;
		$index = 0;
		foreach($element_names as $element_name) {
			$result = subroutineGet($element_name, $types_array[$index++], $variable);
			if ($result)
				$variables_array[$element_name] = $variable;
			else
				break;
		}
		return $result;
	}
	function defaultGet($element_name, $type, $default) {
		if (subroutineGet($element_name, $type, $variable))
			return $variable;
		else
			return $default;
	}
	function checkGets($end_point, $element_names, $types_array, &$variables_array) {
		$result = subroutineGets($element_names, $types_array, $variables_array);
		if (!$result)
			renderIncompleteParameters($end_point);
		return $result;
	}
	function checkGet($end_point, $element_name, $type, &$variables_array) {
		return checkGets($end_point, [$element_name], [$type], $variables_array);
	}
	function checkGetElement($end_point, $element_names, $types_array, $element_name, $type, $values,
			&$variables_array) {
		$result = subroutineGets($element_names, $types_array, $variables_array);
		if ($result) {
			$result = subroutineGet($element_name, $type, $variable) && in_array($variable, $values);
			if ($result)
				$variables_array[$element_name] = $variable;
		}
		if (!$result)
			renderIncompleteParameters($end_point);
		return $result;
	}
	function element($element_name, $variables_array) {
		return [$element_name => $variables_array[$element_name]];
	}
	function elements($element_names, $variables_array) {
		$result = [];
		foreach($element_names as $element_name)
			$result[$element_name] = $variables_array[$element_name];
		return $result;
	}
	function input($element_name) {
		return processInput($_POST[$element_name]);
	}
	function fileInput($file_name) {
		return $_FILES[$file_name];
	}
	function copySetting($name, &$variables_array) {
		$variables_array[$name] = constant("Setting::".$name);
	}
	function copySettings($names, &$variables_array) {
		foreach($names as $name)
			copySetting($name, $variables_array);
	}
	function settings($names) {
		$result = [];
		foreach($names as $name)
			copySetting($name, $result);
		return $result;
	}
	function setting($name) {
		return settings([$name]);
	}
	function arrayScript($array, &$script) {
		if (!empty($array)) {
			foreach($array as $name => $value) {
				if (!empty($script))
					$script .= "&";
				$script .= $name."=".$value;
			}
		}
	}
	function setScript($file, $id, $inputs_array, $extensions_array) {
		$script = "";
		if ($id != 0)
			$script = "id=".$id;
		arrayScript($inputs_array, $script);
		arrayScript($extensions_array, $script);
		$file = escapeString($file);
		if (empty($script))
			return $file;
		else
			return $file."?".$script;
	}
	function setPage($file, $field, $append, &$variables_array) {
		$file = escapeString($file);
		$string = $variables_array[$field];
		if (isset($string)) {
			if (($position = strpos($string, "?")) !== false) {
				$string = $file."?".substr($string, $position + 1);
				if ($append)
					$string .= "&";
			}
			else {
				$string = $file;
				if ($append)
					$string .= "?";
			}
		}
		$variables_array[$field] = $string;
	}
	function setPages($file, $append, &$variables_array) {
		setPage($file, "next", $append, $variables_array);
		setPage($file, "previous", $append, $variables_array);
	}
	function setName($name, &$variables_array) {
		$variables_array["template_name"] = $name;
	}
	function submitCancel(&$action) {
		if (formPost()) {
			if (formSubmit())
				return true;
			else
				$action = "cancel";
		}
		return false;
	}
	function renderPage($template_name, $variables_array = []) {
		$template = new Template();
		$template->render($template_name, $variables_array);
	}
	function renderGetPage($template_name, $id = 0, $call_function = "", $end_point = "") {
		$layer = new Layer();
		if ($id != 0)
			$layer->setId($id);
		if (empty($end_point))
			$end_point = $template_name;
		$layer->map = [HTTPStatus::NOT_FOUND => "dummy"];
		if ($layer->get($end_point)) {
			$variables_array = [];
			$layer->copyResponse($variables_array);
			if (!empty($call_function))
				$call_function($variables_array);
			renderPage($template_name, $variables_array);
		}
		else if (!empty($layer->message))
			renderPage("not-found");
	}
	function renderGetPageId($template_name, $call_function = "", $end_point = "") {
		if (checkGet($template_name, "id", "int", $variables_array))
			renderGetPage($template_name, $variables_array["id"], $call_function, $end_point);
	}
	function renderEditPageId($template_name, $fields_array, &$id, $variables_array = [], $inputs_array = [],
			$extensions_array = [], $call_function = "") {
		global $memorise;
		$memorise = [];
		$layer = new Layer(true, false, false, false);
		$action = "";
		$id = defaultGet("id", "int", 0);
		$layer->setId($id);
		$layer->appends($fields_array);
		$layer->appendValues($inputs_array);
		if ($layer->noResume($fields_array, $variables_array)) {
			if (submitCancel($action)) {
				$layer->setValueInputs($fields_array);
				if ($layer->processFunction($call_function) && $layer->continueResume($template_name, $id)) {
					if ($id == 0) {
						if ($layer->post($template_name."-create")) {
							$id = $layer->responseField("id");
							$action = "post";
						}
					}
					else if ($layer->patch($template_name."-record"))
						$action = "patch";
				}
			}
			else if ($id != 0 && $layer->get($template_name."-record")) {
				$layer->copyResponse($variables_array);
				$memorise = $variables_array;
			}
		}
		if (empty($action)) {
			$variables_array["script"] = setScript(getScriptName(), $id, $inputs_array, $extensions_array);
			$layer->getError($variables_array);
			if (errorMessage($variables_array))
				$layer->setVariables($fields_array, $variables_array);
			renderPage($template_name, $variables_array);
		}
		$alert_name = "";
		if ($action == "post")
			$alert_name = $template_name."-created";
		else if ($action == "patch")
			$alert_name = $template_name."-updated";
		if (!empty($alert_name))
			setAlert($alert_name);
		return $action;
	}
	function renderEditPage($template_name, $fields_array, $variables_array = [], $inputs_array = [],
			$extensions_array = [], $call_function = "") {
		$id = 0;
		return renderEditPageId($template_name, $fields_array, $id, $variables_array, $inputs_array, $extensions_array,
			$call_function);
	}
	function renderEditPageNext($template_name, $fields_array, $next_script_name, $variables_array = [],
			$inputs_array = [],	$extensions_array = [], $parameters_array = [], $call_function = "") {
		$act = renderEditPage($template_name, $fields_array, $variables_array, $inputs_array, $extensions_array,
			$call_function);
		if (!empty($act))
			directToParameters($next_script_name, $parameters_array);
	}
	function renderListPage($template_name, $parameters_array = [], $variables_array = [], $append = false,
			$call_function = "") {
		global $memorise;
		$layer = new Layer();
		$page = defaultGet("page", "int", "");
		$layer->appendUrls($parameters_array);
		if ($layer->get($template_name, $page)) {
			$layer->copyUrls($variables_array);
			$layer->copyResponse($variables_array);
			if (!empty($call_function))
				$call_function($variables_array);
			setName($template_name, $variables_array);
			$variables_array["page"] = $page;
			setPages(getScriptName(), $append, $variables_array);
			$memorise = $variables_array;
			renderPage($template_name, $variables_array);
		}
	}
	function patchNext($script_name, $end_point, $id, $fields_array, $next_script_name, $parameters_array = []) {
		$layer = new Layer();
		$layer->setId($id);
		$layer->appendValues($fields_array);
		if ($layer->patch($end_point)) {
			setAlert($script_name);
			directToParameters($next_script_name, $parameters_array);
		}
	}
	function deleteNext($script_name, $end_point, $id, $next_script_name, $parameters_array = []) {
		$layer = new Layer();
		$layer->setId($id);
		if ($layer->delete($end_point)) {
			setAlert($script_name);
			directToParameters($next_script_name, $parameters_array);
		}
	}
	function errorMessage($variables_array) {
		return !empty($variables_array["error_message"]);
	}
	function renderIncompleteParameters($end_point) {
		renderWebError($end_point, Setting::INCORRECT_PARAMETERS);
	}
	function refreshTokens() {
		$layer = new Layer(false);
		$layer->appendClient();
		$refresh_token = getSession("refresh_token");
		if (!empty($refresh_token)) {
			$layer->appendValues(["grant_type" => "refresh_token", "refresh_token" => $refresh_token]);
			if ($layer->post("auth/token")) {
				$layer->setAuthorisation();
				return true;
			}
		}
		return false;
	}
?>
