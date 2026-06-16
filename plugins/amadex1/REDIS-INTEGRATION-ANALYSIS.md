# Redis Integration – Analysis & Setup Guide

**Level 5 Expert Analysis** | Based on [Redis Cloud](https://redis.io/docs/latest/operate/rc/) and [PHP Predis](https://redis.io/docs/latest/develop/clients/php/) documentation

---

## 1. Redis Cloud Plans & TLS

| Plan | Size | TLS | Connection |
|------|------|-----|------------|
| **Essentials Free** | 30 MB | **No** | Plain TCP only |
| **Essentials Paid** | 250 MB+ | Yes (optional) | TCP or TLS |
| **Pro** | Various | Yes (optional) | TCP or TLS |

**Source:** [TLS docs](https://redis.io/docs/latest/operate/rc/security/database-security/tls-ssl/) – *"TLS is not available for Free Redis Cloud Essentials plans."*

---

## 2. How to Get Redis Cloud Connection Details

Per [Connect to a Redis Cloud database](https://redis.io/docs/latest/operate/rc/databases/connect/):

1. **Log in:** https://cloud.redis.io/ → **Databases**
2. **Select your database** from the list
3. **Click Connect** → opens the connection wizard
4. **Credentials:** In **Configuration** → **Security**:
   - Default user password (click eye icon to show)
   - Username: `default` (unless RBAC is used)
5. **Endpoint:** Host and port appear in the connection wizard (e.g. `redis-12345.redis.cloud.com:12345`)

---

## 3. Redis Official PHP Connection Format

**Basic (plain TCP):**
```php
$r = new Predis\Client([
    'scheme' => 'tcp',
    'host'   => '127.0.0.1',
    'port'   => 6379,
    'password' => '',
    'database' => 0,
]);
```

**With TLS (production):**
```php
$options = [
    'scheme' => 'tls',
    'host'   => 'your-host.redis.cloud.com',
    'port'   => 12345,
    'username' => 'default',
    'password' => 'your-password',
    'options' => [
        'ssl' => [
            'verify_peer' => true,
            'cafile' => './redis_ca.pem',
        ],
    ],
];
```

---

## 4. Our Setup vs Redis Recommendation

| Aspect | Redis recommendation | Amadex current setup | Status |
|--------|----------------------|----------------------|--------|
| **Client** | Predis | Predis + PHP Redis fallback | ✅ |
| **Scheme (Free)** | `tcp` | `tcp` | ✅ |
| **Scheme (Paid TLS)** | `tls` with CA | `rediss` / `tls` with `verify_peer=false` | ⚠️ Works but less strict |
| **TLS for Free** | Do not use | Was auto-enabled; **fixed** | ✅ Fixed |
| **Connection order** | Single scheme | Try `tcp` → `rediss` → `tls` | ✅ |
| **Username** | `default` for Redis Cloud | Optional `redis_username` | ✅ |
| **Host format** | Host only or `host:port` | Supports both | ✅ |

---

## 5. Fix Applied

**Issue:** Amadex forced TLS for any host containing `redis.cloud` or `redislabs.com`. Redis Cloud Free does **not** support TLS, which caused `"packet length too long"` when connecting with TLS to a plain-TCP port.

**Change:** Removed auto-enabling of TLS. The "Use TLS" checkbox now controls TLS, and the UI explains:
- Redis Cloud Free → leave unchecked
- Redis Cloud Paid with TLS enabled → check the box

---

## 6. Amadex Redis Settings Checklist

| Setting | Redis Cloud Free | Redis Cloud Paid (no TLS) | Redis Cloud Paid (TLS on) |
|---------|------------------|---------------------------|---------------------------|
| **Host** | From Connect wizard | Same | Same |
| **Port** | From Connect wizard | Same | Same |
| **Password** | Default user (Configuration → Security) | Same | Same |
| **Username** | `default` or empty | Same | Same |
| **Use TLS** | **Unchecked** | **Unchecked** | **Checked** |

---

## 7. Connection Flow in Code

1. Read settings from `amadex_performance_settings`
2. If TLS enabled: try `tcp` → `rediss` → `tls` (TCP first for compatibility)
3. If TLS disabled: use `tcp` only
4. Predis first for Redis Cloud; PHP Redis as fallback
5. Fallback to WordPress transients if Redis fails

---

## 8. References

- [Connect to Redis Cloud](https://redis.io/docs/latest/operate/rc/databases/connect/)
- [TLS for Redis Cloud](https://redis.io/docs/latest/operate/rc/security/database-security/tls-ssl/)
- [PHP Predis connect](https://redis.io/docs/latest/develop/clients/php/connect/)
- [Essentials plans](https://redis.io/docs/latest/operate/rc/subscriptions/view-essentials-subscription/essentials-plan-details/)
