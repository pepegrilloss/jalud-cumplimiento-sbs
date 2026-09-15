# Reconocimiento para ROP

- Aplicación Laravel 12, PHP 8.2, Filament 3.3, Shield y Spatie Permission.
- Base predeterminada: Cumplimiento. Conexión secundaria `jalud`: fuente operativa de solo lectura.
- El panel `cumplimiento` registra recursos de declaraciones, ROS, catálogos, configuración y usuarios.
- Las operaciones crediticias nacen en `ProposicionCredito` y tienen una fila generada en `Credito`; los pagos están en `pago`.
- El monto original está en `ProposicionCredito.MontoTotal`; la fecha fiable del crédito generado está en `Credito.FechaGeneracion`.
- El código visible del crédito está en `ProposicionCredito.CodigoCredito`.
- Cliente, garante, zona y sede se enlazan por `ClienteID`, `GaranteID`, `ZonaID` y `SedeID`.
- No existe una tabla de garantías separada: el único dato de garantía disponible es el cliente garante.
- El formato ROP tiene una hoja `Modelo ROP`, 35 numerales y tablas oficiales embebidas.
- El formato permite `Otro` para tipo de operación; un préstamo de JALUD se representa allí sin crear un catálogo regulatorio nuevo.
- `RosCaso` ya conserva enlaces a JALUD, personas, operaciones, configuración institucional, workflow y auditoría.
- Los usuarios, roles y permisos de Cumplimiento están separados de JALUD.
- Riesgo existente: el árbol de trabajo contiene cambios ROS y de declaraciones sin confirmar; deben preservarse.

