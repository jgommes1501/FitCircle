# Infraestructura - FitCircle

Documento que detalla la infraestructura técnica, stack tecnológico y requisitos de servidor para la plataforma FitCircle.

---

## 1. Visión general de la infraestructura

FitCircle está diseñada como una plataforma web + app híbrida que requiere una infraestructura escalable, segura y de bajo coste inicial. La arquitectura se basa en tecnologías de código abierto y servicios en la nube para garantizar alta disponibilidad y rendimiento.

### Objetivos de infraestructura

- **Alta disponibilidad:** El sistema debe estar accesible 24/7 sin interrupciones.
- **Escalabilidad:** Capacidad de soportar aumentos de usuarios simultáneos sin degradar el rendimiento.
- **Seguridad:** Protección de datos de usuarios y cumplimiento normativo (RGPD).
- **Bajo coste inicial:** Maximizar el uso de herramientas gratuitas y de código abierto.
- **Mantenibilidad:** Facilitar actualizaciones, deploys y monitoreo continuo.

---

## 2. Stack tecnológico LAMP

FitCircle utiliza una arquitectura LAMP mejorada para el backend y servicios web.

### Componentes principales

| Componente | Tecnología | Versión | Propósito |
|-----------|-----------|---------|----------|
| **Linux** | Ubuntu / CentOS | 20.04+ | Sistema operativo del servidor |
| **Apache** | Apache HTTP Server | 2.4+ | Servidor web y proxy |
| **MySQL** | MySQL / MariaDB | 5.7+ / 10.3+ | Base de datos relacional |
| **PHP** | PHP | 7.4+ / 8.0+ | Lenguaje backend para lógica de negocio |

#### **Lenguaje backend (PHP)**
- PHP-FPM para mejor rendimiento en entornos de producción.
- Soporte de extensiones: PDO, MySQLi, cURL, JSON, GD (imágenes).
- Gestión de sesiones y autenticación segura.
- APIs REST para comunicación con frontend e integración con terceros.

## 3. Desarrollo local: XAMPP

Para el desarrollo local, FitCircle utiliza **XAMPP**, un paquete integrado que incluye:

### Componentes de XAMPP

- **Apache:** Servidor web local.
- **MySQL:** Base de datos relacional local.
- **PHP:** Intérprete PHP para ejecutar scripts.
- **phpMyAdmin:** Interfaz gráfica para gestionar MySQL.
- **Pearl/Python:** Lenguajes adicionales (opcional).



## 4. Frontend: Web y App híbrida

### Stack frontend

| Capa | Tecnologías | Descripción |
|------|-----------|------------|
| **HTML5** | HTML5 estándar | Estructura semántica de páginas |
| **CSS3** | CSS3 + variables CSS | Estilos responsivos y modernos |
| **PHP** | PHP para plantillas | Generación dinámica de contenido |

### Características del frontend

- **Responsive design:** Adaptación a móvil, tablet y desktop.
- **Progressive Web App (PWA):** Funcionamiento offline con service workers.

---

## 5. Arquitectura de hosting


### Servidor en la nube escalable (Recomendado producción)

**Proveedor sugerido:** Vercel, AWS, DigitalOcean, Heroku

```
Vercel (Recomendado para reducir costes):
- Coste inicial: 0 € (Tier Free)
- Despliegue automático desde Git
- CDN global incluido
- SSL automático
- Escalado automático bajo demanda
- Monitoreo y logs incluidos

AWS (Alternativa escalable):
- EC2: t3.micro (Free Tier 1 año)
- RDS: MySQL db.t3.micro
- S3: Almacenamiento de archivos
- CloudFront: CDN global
```

## 6. Base de datos

### Modelo de datos

```sql
Entidades principales:
- usuarios
- dispositivos
- mediciones
- rutas
- espacios_deportivos
- comentarios
- retos
- logros
```

### Conexión a la base de datos (PHP)

```php
<?php
// php - Conexión a la base de datos MySQL
$host = 'localhost';
$usuario = 'fitcircle_user';
$contrasena = 'password_segura';
$basedatos = 'fitcircle_db';

```

## 7. APIs externas integradas

### Fitbit Web API

**Propósito:** Sincronización de datos de actividad física (pasos, calorías, ritmo cardíaco).

```
Endpoint base: https://api.fitbit.com/1/user/-/
Límite gratuito: 1.000 llamadas/día
Autenticación: OAuth 2.0
Datos disponibles:
- Pasos
- Calorías quemadas
- Distancia
- Ritmo cardíaco
- Calidad del sueño
```

### APIs de GPS y Mapas

**Proveedor:** Google Maps API, OpenStreetMap

```
Google Maps API:
- Límite gratuito: $200/mes
- Funcionalidades: Geocoding, Directions, Distance Matrix

```

### Otras integraciones

| API | Propósito | Proveedor |
|-----|-----------|-----------|
| Autenticación social | Login con Facebook, Google | OAuth 2.0 |
| Notificaciones push | Alertas en tiempo real | Firebase Cloud Messaging |
| Pagos | Suscripción premium | Stripe, PayPal |
| Email | Notificaciones por correo | SendGrid, AWS SES |

---

## 8. Seguridad

### Autenticación

- Con php token CSRF para proteger formularios.
- Sessions seguras con tokens CSRF.

### Base de datos

- Prepared statements para prevenir SQL injection.
- Validación y sanitización de inputs en el servidor.
- Encriptación de datos sensibles (SSN, tarjetas, etc.).

### Protección contra ataques

- WAF (Web Application Firewall) en producción.
- Rate limiting para APIs.
- CORS configurado correctamente.
- Actualización regular de dependencias.

---

## 9. Presupuesto de infraestructura

### Desglose de costes mensuales

| Concepto | Coste | Observaciones |
|----------|-------|---------------|
| **Dominio** | 1-2 €/mes | Namecheap, Google Domains |
| **Hosting** | 2-3 €/mes | 25-30 €/año |
| **SSL** | 0 € | Let's Encrypt (gratuito) |
| **Emails** | 0-5 € | Si usa servicio externo |
| **APIs externas** | 0-50 € | Depende de volumen (Fitbit, Maps) |
| **CDN** | 0-10 € | Vercel/Cloudflare |
| **Backups** | 0-5 € | Almacenamiento externo |
| **Monitoreo** | 0 € | Herramientas gratuitas |
| **Total estimado** | **3-75 €/mes** | Según volumen de usuarios |

---

**Última actualización:** 2 de febrero de 2026
**Responsable:** Equipo de desarrollo FitCircle
