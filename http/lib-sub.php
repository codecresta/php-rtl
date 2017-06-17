<?php
	require "consts.php";
	function getEnvironment($variable_name) {
		return boolval(getenv($variable_name));
	}
	function inDevelopmentMode() {
		return getEnvironment("devmode");
	}
	function useContentDeliveryNetwork() {
		return false;
	}
	function checkSession() {
		if (session_status() == PHP_SESSION_NONE)
			session_start();
	}
	function setSession($element_name, $value) {
		checkSession();
		$_SESSION[$element_name] = $value;
	}
	function setSessionResume($element_name, $value) {
		setSession(resumeName($element_name), $value);
	}
	function getSession($element_name) {
		checkSession();
		if (array_key_exists($element_name, $_SESSION))
			return $_SESSION[$element_name];
		else
			return "";
	}
	function getSessionResume($element_name) {
		return getSession(resumeName($element_name));
	}
	function setSessionElement($element_name, $array) {
		setSession($element_name, $array[$element_name]);
	}
	function setSessionElements($element_names, $array) {
		foreach($element_names as $element_name)
			setSessionElement($element_name, $array);
	}
	function getSessionElement($element_name, &$array) {
		$array[$element_name] = getSession($element_name);
	}
	function getSessionElements($element_names, &$array) {
		foreach($element_names as $element_name)
			getSessionElement($element_name, $array);
	}
	function unsetSession($element_name) {
		checkSession();
		if (array_key_exists($element_name, $_SESSION))
			unset($_SESSION[$element_name]);
	}
	function unsetSessions($element_names) {
		foreach($element_names as $element_name)
			unsetSession($element_name);
	}
	function unsetSessionResume($element_name) {
		unsetSession(resumeName($element_name));
	}
	function clearSession() {
		checkSession();
		session_unset();
		session_destroy();
	}
	function directTo($script_name, $end_string = "") {
		if (!empty($end_string))
			$end_string = "?".$end_string;
		header("Location: ".$script_name.".php".$end_string);
		exit();
	}
	function directToParameters($script_name, $parameters_array) {
		$end_string = "";
		foreach($parameters_array as $name => $value) {
			if (!empty($end_string))
				$end_string .= "&";
			$end_string .= $name."=".$value;
		}
		directTo($script_name, $end_string);
	}
	function directToParameter($script_name, $name, $value) {
		directToParameters($script_name, [$name => $value]);
	}
	function renderAPIError($variables_array) {
		$template = new Template();
		$template->render("api-error", $variables_array);
		exit();
	}
	function renderError($message, $end_point, $code) {
		$variables_array["error_message"] = $message;
		$variables_array["error_text"] = "End point: ".$end_point;
		$variables_array["error_code"] = $code;
		renderAPIError($variables_array);
	}
	function renderWebError($end_point, $code) {
		renderError("A web server error has occured.", $end_point, $code);
	}
	function renderClientError($end_point, $code) {
		renderError("A client error has occured.", $end_point, $code);
	}
	class Layer {
		private $authorise;
		private $defer_error;
		private $multipart_form;
		private $fields_array = [];
		private $end_point;
		private $curl_handle;
		public $curl_info;
        public $curl_errno;
        public $curl_error;
		public $response;
		public $code;
		public $message;
		public $map = [];
		public $flag;
		public function __construct($authorise = true, $defer_error = true, $multipart_form = false,
				$check_login = true) {
			$this->authorise = $authorise;
			$this->defer_error = $defer_error;
			$this->multipart_form = $multipart_form;
			$this->code = "";
			$this->message = "";
			$this->flag = false;
			if ($authorise && $check_login && $this->sessionExpire())
				directTo("login");
		}
		public function append($field_name) {
			$this->fields_array[$field_name] = [];
			$this->fields_array[$field_name]["url"] = false;
			$this->fields_array[$field_name]["value"] = "";
		}
		public function appends($fields_array) {
			foreach($fields_array as $field_name)
				$this->append($field_name);
		}
		public function appendValue($field_name, $value) {
			$this->append($field_name);
			$this->fields_array[$field_name]["value"] = $value;
		}
		public function appendValues($fields_array) {
			foreach($fields_array as $field_name => $value)
				$this->appendValue($field_name, $value);
		}
		public function setUrl($field_name) {
			$this->fields_array[$field_name]["url"] = true;
		}
		public function appendUrl($field_name, $value) {
			$this->appendValue($field_name, $value);
			$this->setUrl($field_name);
		}
		public function appendUrls($fields_array) {
			foreach($fields_array as $field_name => $value)
				$this->appendUrl($field_name, $value);
		}
		public function setValue($field_name, $value) {
			$this->fields_array[$field_name]["value"] = $value;
		}
		public function setValueInput($field_name) {
			$this->setValue($field_name, input($field_name));
		}
		public function setValueInputs($fields_array) {
			foreach($fields_array as $field_name)
				$this->setValueInput($field_name);
		}
		public function copyUrls(&$variables_array) {
			foreach($this->fields_array as $name => $field)
				if ($field["url"])
					$variables_array[$name] = $field["value"];
		}
		public function setId($id) {
			$this->append("id");
			$this->setUrl("id");
			$this->setValue("id", $id);
			return $id;
		}
		public function processFunction($call_function) {
			if (empty($call_function))
				return true;
			else
				return $call_function($this, $this->message);
		}
		public function value($field_name) {
			return $this->fields_array[$field_name]["value"];
		}
		public function setVariable($name, &$variables_array) {
			$variables_array[$name] = $this->value($name);
		}
		public function setVariables($variable_names_array, &$variables_array) {
			foreach($variable_names_array as $name)
				$this->setVariable($name, $variables_array);
		}
		private function urlParameters() {
			$result = "";
			foreach($this->fields_array as $name => $field)
				if ($field["url"])
					$result .= "/".$field["value"];
			return $result;
		}
		public function continueResume($template_name, $id) {
			if ($this->sessionExpire()) {
				setSessionResume("", $template_name);
				setSessionResume("id", $id);
				foreach($this->fields_array as $name => $field)
					setSessionResume($name, $field["value"]);
				directTo("login");
			}
			else
				return true;
		}
		public function noResume($variable_names_array, &$variables_array) {
			$template_name = getSessionResume("");
			if (empty($template_name))
				return true;
			else {
				unsetSessionResume("");
				unsetSessionResume("id");
				foreach($variable_names_array as $variable_name)
					if ($variable_name != "id") {
						$variables_array[$variable_name] = getSessionResume($variable_name);
						unsetSessionResume($variable_name);
					}
				return false;
			}
		}
		function appendClient() {
			$this->appendValues(["client_id" => Setting::CLIENT_ID, "client_secret" => Setting::CLIENT_SECRET]);
		}
		function setAuthorisation() {
			$field_names_array = ["access_token", "refresh_token", "expires_in"];
			$this->setFieldSessions($field_names_array);
			$this->setExpireSession();
		}
		private function initialise($end_point) {
			$this->end_point = $end_point;
			$this->curl_handle = curl_init(getURL($end_point));
			$success = true;
			$http_header = [];
			if ($this->authorise) {
				$token = getSession("access_token");
				$success = !empty($token);
				if ($success)
					array_push($http_header, "Authorization: Bearer ".$token);
			}
			if ($success) {
				if ($this->multipart_form)
					array_push($http_header, "Content-Type: multipart/form-data");
				if (!empty($http_header))
					curl_setopt($this->curl_handle, CURLOPT_HTTPHEADER, $http_header);
				curl_setopt($this->curl_handle, CURLOPT_RETURNTRANSFER, true);
			}
			else
				renderClientError($end_point, Setting::COULD_NOT_COMPLETE);
			return $success;
		}
		private function initialiseParameters($end_point, $page = "") {
			$end_point .= $this->urlParameters();
			if (!empty($page))
				$end_point .= "?page=".$page;
			return $this->initialise($end_point);
		}
		private function execute() {
			$response = curl_exec($this->curl_handle);
			$this->curl_info = curl_getinfo($this->curl_handle);
            $this->curl_errno = curl_errno($this->curl_handle);
            $this->curl_error = curl_error($this->curl_handle);
			curl_close($this->curl_handle);
			$this->response = json_decode($response, true);
		}
		private function httpCode() {
			global $http_code;
			$element = $this->curl_info["http_code"];
			return "Code ".$element.": ".(array_key_exists($element, $http_code) ? $http_code[$element] : "");
		}
		private function logError() {
			$new_line = "\r\n";
			$error_string = date("Y-m-d H:i:s").$new_line.$this->httpCode().$new_line;
			if (is_array($this->response)) {
				foreach($this->response as $field_name => $value) {
					$error_string .= $field_name.": ";
					if (is_array($value)) {
						$values_string = "";
						foreach($value as $text) {
							if (!empty($values_string))
								$values_string .= ", ";
							$values_string .= $text;
						}
						$error_string .= $values_string;
					}
					else
						$error_string .= $value;
					$error_string .= $new_line;
				}
			}
			else if (!empty($this->response))
				$error_string .= $this->response.$new_line;
            $error_string .= "Curl error no: ".$this->curl_errno.$new_line;
            $error_string .= "Curl error: ".$this->curl_error.$new_line;
			$error_string .= $new_line;
			file_put_contents(Setting::API_ERROR_LOG, $error_string, FILE_APPEND);
		}
		private function checkStatus($status_array) {
			$element = $this->curl_info["http_code"];
			if (in_array($element, $status_array))
				return true;
			else {
				if (inDevelopmentMode())
					$this->logError();
				if (array_key_exists($element, $this->map)) {
					$this->message = $this->map[$element];
					$this->flag = true;
				}
				else {
					$set = true;
					if ($element == HTTPStatus::BAD_REQUEST && count($this->response) > 0) {
						$details = reset($this->response);
						if (key($this->response) == "detail") {
							$set = false;
							$message = "";
							if (is_array($details)) {
								foreach($details as $text) {
									if (!empty($message))
										$message .= "  ";
									$message .= $text;
								}
							}
							else
								$message = $details;
							$this->message = $message;
						}
					}
					if ($set)
						$this->code = $this->httpCode();
					if ($this->defer_error)
						$this->apiError();
				}
				return false;
			}
		}
		private function statusOK() {
			return $this->checkStatus([HTTPStatus::OK]);
		}
		private function statusOKCreated() {
			return $this->checkStatus([HTTPStatus::OK, HTTPStatus::CREATED]);
		}
		private function statusNoContent() {
			return $this->checkStatus([HTTPStatus::NO_CONTENT]);
		}
		public function get($end_point, $page = "") {
			if ($this->initialiseParameters($end_point, $page)) {
				$this->execute();
				return $this->statusOK();
			}
			else
				return false;
		}
		public function dataExecute() {
			$curl_data = [];
			foreach($this->fields_array as $name => $field)
				if (!$field["url"])
					$curl_data[$name] = $field["value"];
			curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS, $curl_data);
			$this->execute();
		}
		public function post($end_point) {
			if ($this->initialise($end_point)) {
				curl_setopt($this->curl_handle, CURLOPT_POST, true);
				$this->dataExecute();
				return $this->statusOKCreated();
			}
			else
				return false;
		}
		public function patch($end_point) {
			if ($this->initialiseParameters($end_point)) {
				curl_setopt($this->curl_handle, CURLOPT_CUSTOMREQUEST, "PATCH");
				$this->dataExecute();
				return $this->statusOK();
			}
			else
				return false;
		}
		public function delete($end_point) {
			if ($this->initialiseParameters($end_point)) {
				curl_setopt($this->curl_handle, CURLOPT_CUSTOMREQUEST, "DELETE");
				$this->dataExecute();
				return $this->statusNoContent();
			}
			else
				return false;
		}
		public function responseField($field_name) {
			return $this->response[$field_name];
		}
		public function copyResponse(&$destination) {
			$destination = array_merge($destination, $this->response);
		}
		public function getError(&$variables_array) {
			if (empty($this->code))
				$variables_array["error_message"] = $this->message;
			else {
				$variables_array["error_message"] = "An API error has occured.";
				$variables_array["error_text"] = "End point: ".$this->end_point;
				$variables_array["error_code"] = $this->code;
				$variables_array["error_details"] = $this->response;
			}
		}
		function apiError() {
			$this->getError($variables_array);
			renderAPIError($variables_array);
		}
		public function setFieldSession($name) {
			setSession($name, $this->responseField($name));
		}
		public function setFieldSessions($names_array) {
			foreach($names_array as $name)
				$this->setFieldSession($name);
		}
		public function setExpireSession() {
			$seconds = intval($this->responseField("expires_in")) - Setting::EXPIRE_SECONDS;
			setSession("expire_dt_tm", date(Setting::DT_TM_FORMAT, strtotime("+".$seconds." seconds")));
		}
		public function sessionExpire() {
			$date_time = getSession("expire_dt_tm");
			if (empty($date_time))
				return true;
			else
				if (date(Setting::DT_TM_FORMAT) > $date_time)
					return !refreshTokens();
				else
					return false;
		}
	}
	class Template {
		private $environment;
		public function __construct() {
			$loader = new Twig_Loader_Filesystem(Setting::TEMPLATE_DIRECTORY);
			$this->environment = new Twig_Environment($loader, ["cache" => Setting::TWIG_COMPILATION_DIRECTORY,
				"debug" => inDevelopmentMode()]);
		}
		public function render($template_name, $variables_array = []) {
			$template = $this->environment->loadTemplate($template_name.".twig");
			$variables_array["cdn"] = useContentDeliveryNetwork();
			setName($template_name, $variables_array);
			$variables_array["title"] = ucwords(str_replace("-", " ", $template_name));
			echo $template->render($variables_array);
		}
	}
?>
