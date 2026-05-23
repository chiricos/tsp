# Drupal Development Skill

## Contexto
Trabajamos con Drupal 11, basado en Composer, Drush y buenas prácticas modernas.

## Reglas de desarrollo
- Usar servicios en lugar de código procedural
- Seguir PSR-4 y PSR-12
- Evitar lógica en controllers (usar services)
- Usar dependency injection, no \Drupal::

## Módulos personalizados
Estructura estándar:
- modulename.info.yml
- modulename.module
- src/
- modulename.services.yml

## Comandos frecuentes
- drush cr
- drush updb
- drush cim

## Buenas prácticas
- No modificar módulos contrib
- Usar patches vía composer
- Validar cache tags y contexts

## Respuestas esperadas del agente
- Código listo para producción
- Explicaciones cortas
- Ejemplos claros