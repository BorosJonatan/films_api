<?php

namespace App\Html;

class Response
{
    private array $payload = [];
    private int $status = 200;

    public static function json(): self {
        return new self();
    }

    public static function response(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(
            [
                'code' => $status,
                'data' => $data
            ],
            JSON_THROW_ON_ERROR
        );
        exit;
    }

    public function withData(array $data): self {
        $this->payload = $data;
        return $this;
    }

    public function withStatus(int $status): self {
        $this->status = $status;
        return $this;
    }

    public function send(): void {
        http_response_code($this->status);
        header('Content-Type: application/json');
        echo json_encode(['code' => $this->status, 'data' => $this->payload], JSON_THROW_ON_ERROR);
        exit;
    }

    function jsonResponse(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['code' => $code, 'data' => $data], JSON_THROW_ON_ERROR);
        exit;
    }
    public static function errorResponse(string $message, int $code = 400): void {
        jsonResponse(['error' => $message], $code);
    }
}