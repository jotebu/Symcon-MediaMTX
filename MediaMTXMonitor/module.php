<?php

declare(strict_types=1);

class MediaMTXMonitor extends IPSModule
{
    private const STATUS_ACTIVE = 102;
    private const STATUS_HOST_MISSING = 200;
    private const STATUS_API_UNREACHABLE = 201;
    private const STATUS_INVALID_RESPONSE = 202;

    public function Create(): void
    {
        parent::Create();

        $this->RegisterPropertyString('Host', '127.0.0.1');
        $this->RegisterPropertyInteger('APIPort', 9997);
        $this->RegisterPropertyBoolean('UseHTTPS', false);
        $this->RegisterPropertyBoolean('VerifyTLS', true);
        $this->RegisterPropertyInteger('UpdateInterval', 30);

        $this->RegisterVariableBoolean('APIReachable', 'API erreichbar', '~Switch', 10);
        $this->RegisterVariableString('Version', 'MediaMTX-Version', '', 20);
        $this->RegisterVariableString('LastSuccessfulUpdate', 'Letzter erfolgreicher Abruf', '', 30);
        $this->RegisterVariableString('LastError', 'Letzter Fehler', '', 40);
        $this->RegisterVariableString('RawInfo', 'API-Rohdaten /v3/info', '', 50);

        $this->RegisterTimer('UpdateTimer', 0, 'MMTX_Update($_IPS[\'TARGET\']);');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $interval = max(5, $this->ReadPropertyInteger('UpdateInterval'));
        $this->SetTimerInterval('UpdateTimer', $interval * 1000);

        if (trim($this->ReadPropertyString('Host')) === '') {
            $this->SetStatus(self::STATUS_HOST_MISSING);
            $this->SetValue('APIReachable', false);
            return;
        }

        $this->Update();
    }

    public function Update(): bool
    {
        $host = trim($this->ReadPropertyString('Host'));
        if ($host === '') {
            $this->SetStatus(self::STATUS_HOST_MISSING);
            $this->SetValue('APIReachable', false);
            $this->SetValue('LastError', 'Host oder IP-Adresse fehlt.');
            return false;
        }

        $url = $this->BuildApiUrl('/v3/info');
        $this->SendDebug('Request', 'GET ' . $url, 0);

        $response = $this->HttpGet($url);
        if ($response === false) {
            return false;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            $this->SetStatus(self::STATUS_INVALID_RESPONSE);
            $this->SetValue('APIReachable', false);
            $this->SetValue('LastError', 'Die Antwort von /v3/info ist kein gueltiges JSON-Dokument.');
            $this->SetValue('RawInfo', $response);
            $this->SendDebug('JSON error', json_last_error_msg(), 0);
            return false;
        }

        $version = '';
        if (isset($decoded['version']) && is_string($decoded['version'])) {
            $version = $decoded['version'];
        }

        $prettyJson = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($prettyJson === false) {
            $prettyJson = $response;
        }

        $this->SetValue('APIReachable', true);
        $this->SetValue('Version', $version);
        $this->SetValue('LastSuccessfulUpdate', date('d.m.Y H:i:s'));
        $this->SetValue('LastError', '');
        $this->SetValue('RawInfo', $prettyJson);
        $this->SetStatus(self::STATUS_ACTIVE);
        $this->SendDebug('Response', $prettyJson, 0);

        return true;
    }

    private function BuildApiUrl(string $path): string
    {
        $scheme = $this->ReadPropertyBoolean('UseHTTPS') ? 'https' : 'http';
        $host = trim($this->ReadPropertyString('Host'));
        $port = $this->ReadPropertyInteger('APIPort');

        if (str_starts_with($host, '[') || filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            $formattedHost = $host;
        } else {
            $formattedHost = '[' . $host . ']';
        }

        return sprintf('%s://%s:%d%s', $scheme, $formattedHost, $port, $path);
    }

    private function HttpGet(string $url): string|false
    {
        $curl = curl_init($url);
        if ($curl === false) {
            $this->HandleConnectionError('cURL konnte nicht initialisiert werden.');
            return false;
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => $this->ReadPropertyBoolean('VerifyTLS'),
            CURLOPT_SSL_VERIFYHOST => $this->ReadPropertyBoolean('VerifyTLS') ? 2 : 0
        ]);

        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false) {
            $this->HandleConnectionError($curlError !== '' ? $curlError : 'Unbekannter cURL-Fehler.');
            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $this->HandleConnectionError(sprintf('HTTP-Status %d von /v3/info.', $httpCode));
            $this->SendDebug('HTTP response', (string) $response, 0);
            return false;
        }

        return (string) $response;
    }

    private function HandleConnectionError(string $message): void
    {
        $this->SetStatus(self::STATUS_API_UNREACHABLE);
        $this->SetValue('APIReachable', false);
        $this->SetValue('LastError', $message);
        $this->SendDebug('Connection error', $message, 0);
    }
}
