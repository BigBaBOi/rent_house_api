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
            $this->bodyData = [];
            return [];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON payload');
        }

        $this->bodyData = $decoded;
        return $decoded;
    }
}
