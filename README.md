# Symcon-MediaMTX

IP-Symcon module for monitoring a MediaMTX server through its Control API and metrics endpoint.

## Initial scope

- Check whether the MediaMTX Control API is reachable
- Read and display the MediaMTX version
- Discover active paths
- Show stream availability and active readers per path
- Calculate inbound and outbound bitrates from MediaMTX byte counters
- Provide selected values for later use in IP-Symcon, Basalte and KNX

## Development status

The first development stage implements the module structure, connection settings and the MediaMTX `/v3/info` request. Path monitoring and bitrate calculation will follow in separate stages.

## MediaMTX preparation

Enable the Control API in `mediamtx.yml`:

```yaml
api: yes
```

The default Control API address is `127.0.0.1:9997`. When IP-Symcon runs on another device, the API must be exposed deliberately and protected appropriately.

Metrics will later be enabled with:

```yaml
metrics: yes
```

## Repository structure

```text
MediaMTXMonitor/
  module.php
  module.json
  form.json
library.json
docs/
  API.md
```
