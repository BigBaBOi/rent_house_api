<?php
class Request {
    private $method;
    private $queryParams;
    private $bodyData;

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->queryParams = $_GET;
        $this->bodyData = null;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getQuery($name, $default = null) {
        if (!isset($this->queryParams[$name])) {
            return $default;
        }

        return trim($this->queryParams[$name]);
    }

    public function getJsonBody() {
        if ($this->bodyData !== null) {
            return $this->bodyData;
        }

        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            $this->bodyData = !empty($_POST) ? $_POST : [];
            return $this->bodyData;
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (!empty($_POST)) {
                $this->bodyData = $_POST;
                return $_POST;
            }
            parse_str($raw, $parsed);
            if (!empty($parsed)) {
                $this->bodyData = $parsed;
                return $parsed;
            }
            throw new InvalidArgumentException('Invalid payload format');
        }

        $this->bodyData = $decoded;
        return $decoded;
    }
}
