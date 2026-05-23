# Drupal Security Skill

## Headers obligatorios
- Content-Security-Policy
- X-Frame-Options
- Strict-Transport-Security

## Reglas
- Nunca usar 'unsafe-inline' si no es necesario
- Validar origen de scripts externos
- Evitar exponer rutas administrativas

## AWS
- Usar ALB + WAF cuando sea posible
- Restringir IPs sensibles

## Validaciones
- curl -I para headers
- Revisar duplicados (ej: HSTS duplicado)

## Alertas
- Priorizar SA-CORE de Drupal
- Aplicar parches críticos inmediatamente