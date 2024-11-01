=== Shipit ===
Contributors: franciscoarenasp
Donate link: https://shipit.cl/
Tags: shipping, calculator, couriers, Shipit
Requires at least: 4.4
Tested up to: 6.5
Requires PHP: 5.6
Stable tag: 9.3.0
Version: 9.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Shipit Calculator Mensajeros de envío

== Description ==
Recuerda necesitas estas versiones para que funcione perfectamente:

WC Version: 3.5.x or later
WP Version:	4.4 or later

Hola, está a punto de instalar nuestro complemento de última milla para su logística de comercio electrónico.

Donde puede integrarse con el sistema de envío de Shipit y acelerar todas las entregas de sus productos.
Al integrar su tienda a nuestra plataforma, puede ver, administrar y modificar sus envíos rápidamente con más de 5 couriers de Chile.

Solo tiene que poseer sus credenciales otorgadas por Shipit.

* etiquetas: envío, paquetería, logística, fijación de precios, administración del estado del envío.


== Installation ==

1. Cargue los archivos del complemento en el directorio `/ wp-content / plugins / shipit`, o instale el complemento a través de la pantalla de complementos de WordPress directamente.
2. Active el complemento a través de la pantalla 'Complementos' en WordPress
3. Use la pantalla Configuración-> Nombre del complemento para configurar el complemento
4. Cree una pestaña llamada `Configuración Shipit`, donde debe agregar su usuario otorgado por Shipit.
5. Listo ya está integrado con el presupuesto de envío y la gestión de sus pedidos.

Para más información, visita nuestro centro de ayuda a través del siguiente link: https://shipitcl.zendesk.com/hc/es-419/articles/360016135074--C%C3%B3mo-integrar-mi-tienda-de-WooCommerce-con-Shipit-

== Frequently Asked Questions ==

= ¿Qué puede hacer mi plugin? =
Gestionar tus pedidos desde la plataforma Shipit.

= ¿Puedo enviar los pedidos ya generados? =

Sí, con las acciones en lote, más información en el siguiente link: https://shipitcl.zendesk.com/hc/es-419/articles/360042204934--C%C3%B3mo-enviar-mis-pedidos-manualmente-desde-WooCommerce-a-Shipit-

== Screenshots ==

1. Credential Entry page `/ src / images / screenshot-1.png`
2. Field cotizador messengers `/ src / images / screenshot-2.png`

== Changelog ==
= 9.3.0 =
* implementación cubone para cubicación

= 9.2.3 =
* corrección logo envío gratis checkout y precios

= 9.2.2 =
* descripción de courier desde core

= 9.2.1 =
* corrección comunas duplicadas

= 9.2.0 =
* valida si cliente no ingresa número de dirección

= 9.1.0 =
* agregar medidas por defecto cuando se envía la orden a shipit

= 9.0.0 =
* agregar modulo de instalación

= 8.18.0 =
* Refactor

= 8.17.3 =
* Corrige error con componente de producto

= 8.17.2 =
* Migrar configuraciones restantes

= 8.17.1 =
* Migrar configuraciones desde tarificación

= 8.17.0 =
* Mover configuraciones de ajustes de precio a Suite

= 8.16.1 =
* Arregla estilo de multiselect en configuraciones.

= 8.16.0 =
* Unificar la vista de configuraciones con la vista de credenciales en el menú principal

= 8.15.1 =
* soluciona el problema de redundancia cíclica de función

= 8.15.0 =
* cambiar el evento de envío shipit de ingresar a la vista de agradecimiento a cuando cambia de estado una órdene
* agregar mensaje desde core por cuenta bloqueada por pago en la vista de credenciales

= 8.14.0 =
* Agregar control de errores mediante Mix Panel
* fix error suma de pesos menores a 1 kg

= 8.13.3 =
* visualización logo courier Recíbelo Next Day

= 8.13.2 =
* Eliminar fondo logos Recíbelo

= 8.13.1 =
* Cambiar logo de courier Recíbelo Same Day

= 8.13.0 =
* Agregar logo de courier Recíbelo Next Day

= 8.12.0 =
* actualización de tarificación en checkout sólo con el campo comunas
* corrección para la actualización de estados
* validar si usuario shipit existe y actualiza información
* corrección de almacenamiento de token bugsnag
* corrección en la creación de client id para tiendas con nombres compuestos
* agregar validación para numeración de la dirección
* remover llamado a opit para verificar si es calculadora v3 y acelerar proceso de tarificación
* Sólo actualizar tarificaciones cuando hay cambio de comuna

= 8.11.7 =
* Cambio de logo Motopartner a Jawi

= 8.11.6 =
* agrega clase debug

= 8.11.5 =
* corrección enviar a shipit por acción masiva para última versión de woocommerce
* corrección etiqueta de envío erronea a Shipit cuando efectivamente si se envía
* corrección error falta columna bt

= 8.11.4 =
* Mejora en la detección de direcciones de envío

= 8.11.3 =
* Corrección mínima para logos de couriers. (Actualización opcional)

= 8.11.2 =
* Agregar logo de courier Yango

= 8.11.1 =
* Corrección para evitar falla en la aplicación al ingresar credenciales incorrectas

= 8.11.0 =
* Agregar logos para welivery, fedex mx y estafeta mx.

= 8.10.2 =
* agregar courier_name para clase same day.

= 8.11.0 =
* Agregar logos para welivery, fedex mx y estafeta mx.

= 8.10.1 =
* Cambiar valor por defecto del atributo platform en la clase Order y validar Same Day en envío complementario.

= 8.10.0 =
* Migrar envíos a v4

= 8.9.1 =
* Corregir error de instalación en clientes nuevos con versiones superiores de php 8.

= 8.9.0 =
* Agregar ID del canal, a ventas y envíos

= 8.8.2 =
* Agregar logo para HC Group, Rayo y ChilePost.

= 8.8.1 =
* Remover la versión del plugin de las sesiones del cliente.

= 8.8.0 =
* Cambiar etiquetas 'Colonias' por 'Municipio o Delegación'.

= 8.7.0 =
* Agregar logo de Moova para México.

= 8.6.0 =
* Añadir prefijo a la función que crea e importa las comunas.

= 8.5.0 =
* Validar salidas de elementos html.

= 8.4.0 =
* Sanitizar data.

= 8.3.0 =
* No llamar archivos de forma remota.

= 8.2.0 =
* Actualizar hasta la versión probada de Wordpress en Readme.

= 8.1.4 =
* Incrementar límite de timeout para request de método post.

= 8.1.3 =
* Corrección error javascript en vista de pago.

= 8.1.2 =
* Agregar verificación de plugin al hook de actualización.

= 8.1.1 =
* Poblar tabla comunas después de actualizar plugin.

= 8.1.0 =
* Importar país de origen desde la cuenta Shipit.

= 8.0.1 =
* RollBack México

= 8.0.0 =
* Adaptar plugin para funcionamiento en tiendas de México

= 7.1.1 =
* Modificar Workflow para publicación de plugin.

= 7.1.0 =
* Mover el proceso de envío de configuraciones al crontab para mejorar el rendimiento.

= 7.0.0 =
* Almacenar tarificaciones cuando estan activas las tarifas de emergencia locales, para poder enviarlas cuando retorne el servicio.

= 6.0.2 =
* Agregar nodo de envío automático en las configuraciones de integración

= 6.0.1 =
* Agregar nodo de same day en las configuraciones de integración

= 6.0.0 =
* Enviar información relevante en las tarificaciones

= 5.1.2 =
* Corrección a la forma de acceder a las configuraciones de Shipit en el envío por acción masiva

= 5.1.1 =
* Agregar función para obtener los plugins cuando no exista en el espacio de trabajo

= 5.1.0 =
* actualizar logos Shipit

= 5.0.0 =
* habilitar webhook para poder consultar las configuraciones locales y plugins instalados

= 4.2.0 =
* Validar si compras Same Day se crean como envíos u ordenes
* transformar precio de checkout a decimal en tarificación
* bajar timeout del método post de 60 a 10

= 4.1.3 =
* Crear colecciones de medidas por pedido en acción masiva
* agregar test unitario para la creación de ordenes
= 4.1.2 =
* No ofrecer envío cuando no hay cobertura

= 4.1.1 =
* Retornar Tarifas de Emergencia cuando no funcione el servicio y esté activado multicourier

= 4.1.0 =
* Agregar el precio de los productos a parcel

= 5.0.0 =
* Almacenar las configuraciones locales en core

= 4.0.0 =
* Quitar origen a parcel para resolverlo en Core
* Agregar test unitario para tarificación

= 3.6.2 =
* Corregir logo y descripción envío GRATIS

= 3.6.1 =
* Seleccionar sin courier cuando sea despacho normal a domicilio

= 3.6.0 =
* Remover selector de algoritmo de la vista de configuración

= 3.5.0 =
* Agrega la funcionalidad para aceptar envíos con entrega el mismo día.

= 3.4.3 =
* Mostrar tarifas de emergencia cuando el servicio de tarificación no funcione.
* Crear endpoint para recibir tarifas de emergencias
* Almacenar tarifas de emergencia en la base de datos

= 3.3.6 =
* Estabilizar para PHP 8

= 3.3.5 =
* Arregla problema de creación de envíos en acción masiva

= 3.3.4 =
* previene warning por index inexistente

= 3.3.3 =
* se adjuntan archivos faltantes

= 3.3.2 =
*Validando clase Shipit_Shipping antes de llamar a la clase refreshShippingRates para evitar errores de la versión 3.3.0
*Enviando de forma correcta la fecha de creación de la orden en tienda al API de shipit

= 3.3.1 =
*Validar estados de shipit enviados a la actualización de órdenes
*Evitar creación de envios cuando el metodo sea recogida local Y este activa la opción de generación de envios automática
*Correción de envio de dimesiones para los clientes fulfillment
*Cambio de logo 99minutos

= 3.3.0 =
*Consolidar tarificación para medición, en el carro de compra.

= 3.2.0 =
*No agregar los pedidos "retiro en tienda" al método complementario de envío.

= 3.1.0 =
*Corrección método para obtener las propiedades de las dimensiones.
*Agregamos bugsnag para capturar errores en los servicios de creación de envíos y órdenes.
*Opción para seleccionar todas las comunas en el apartado de configuraciones

= 3.0.16 =
*Corrección método para obtener las propiedades de order.
*Agregamos un log para guardar la respuesta del servicio al crear el envío.


= 3.0.15 =
*Corrección para función de envío automático.
*Corrección a dimensiones mostradas en Shipit.
*Corrección ortográfica en campo de Teléfono.

= 3.0.14 =
*Ver en Shipit únicamente ventas que tengan método de envío Shipit.

= 3.0.13 =
*Corrección de comuna seleccionada para envíos con dirección de despacho diferida.

= 3.0.12 =
*Agregamos nueva unidad de peso (g) para compatibilidad con configuación Woocommerce.
*Correcciones en extracción de direcciones.
*Implementación de configuración de tarifa plana, configuración en Shipit.

= 3.0.11 =
*Correcciones en formato de parámetros para realizar cálculo de medidas estimadas.

= 3.0.10 =
*Normalizamos y mejoramos el cálculo de medidas estimadas para realizar cotizaciones con la API de Shipit.

= 3.0.9 =
*Mejora en extraccion de dirección de destino al momento de enviar a Shipit.
*Corrección a error al momento de intentar crear ventas en Shipit luego de recibir el pago de una venta.

= 3.0.8 =
*Mejora en extracción de dirección de destino, vía acción masiva.

= 3.0.7 =
*Validación de couriers desplegados bajo algoritmo Shipit.

= 3.0.6 =
*Corrección de métodos de despacho únicos.

= 3.0.5 =
*Correcciones de acción en lote y envío de dirección.

= 3.0.4 =
*Corrección de versionamiento.

= 3.0.3 =
*Validaciones de formato para compatibilidad con Front-office.

= 3.0.2 =
*Correcciones de mensajes de tipo warning al momento de instalar

= 3.0.1 =
*Modificaciones menores en el API de conexión

= 3.0.0 =
*Validaciones de administración de destinos.
*Correcciones al flujo de instalacion, cotizacion, creacion de envío

= 2.6.5 =
*Validación para detección de ventas en Shipit con estado "Confirmada".

= 2.6.4 =
*Validación para no mostrar órdenes en Shipit que hayan tenido un método de envío diferente.

= 2.6.3 =
*Validación para detección de seguro adicional.

= 2.6.2 =
*Realización de requests sin https.

= 2.6.1 =
*Mejora configuraciones de pantalla de pagos.
*Orden alfabético en selector de comunas.
*Mejora en validación de órdenes con método de envío Shipit.

= 2.6.0 =
*Habilita configuraciones de pantalla de pagos desde tu integración.

= 2.5.0 =
*Habilita configuraciones desde tu integración

= 2.4.1 =
*Agrega correcciones mínimas de visualización.

= 2.4.0 =
*Agrega compatibilidad con seguro adicional.

= 2.3.1 =
*Sincroniza SKUS de clientes fulfillment con SKUs no reconocidos.

= 2.3.0 =
*Sincroniza SKUS de clientes fulfillment y los carga a la venta
*Mejora en la tarificación

= 2.2.10 =
*Correcciones a las medidas de los productos

= 2.2.9 =
*Validadores productos en 0, acciones masivas pesos recalculados

= 2.2.8 =
*Adición de servicio pp ff

= 2.2.7 =
*Adición de imágenes de plataforma y cambios de endpoint

= 2.2.6 =
*Precios y dimensiones para Suite

= 2.2.5 =
*Corrección de split de addres_fields

= 2.2.4 =
*Envios costo cero reemplazados por 'GRATIS'

= 2.2.3 =
*corrección calculo variaciones de tamaño unitario

= 2.2.2 =
*corrección tarificador de variaciones

= 2.2.1 =
*Se solventó un posible problema de bloqueo de ip

= 2.2.0 =
*Se cambiaron los estados de los pedidos y se solucionaron los problemas con los pedidos sin estados

= 2.1.1 =
*los pedidos que no se encuentren completados o en proceso no serán enviados

= 2.0.1 =
*se corrigen problemas con algunas imágenes

= 2.0 =
*Se crearon configuraciones de envío
*costos personalizados
*comunas especificas
*envíos automáticos
*validadores de respuesta
*desabilitar couriers
*medidas estandar
*soporte a diferentes unidades de medidas
*corrección a zonas de envios

= 1.1.1 =
Upgrade notices describe the reason a user should upgrade.  No more than 300 characters.


== Upgrade Notice ==

= 2.2.9 =
*Validadores productos en 0, acciones masivas pesos recalculados

= 2.2.8 =
*Adición de servicio pp ff

= 2.2.7 =
*Adición de imágenes de plataforma y cambios de endpoint

= 2.2.6 =
*Precios y dimensiones para Suite

= 2.2.5 =
*Corrección de split de addres_fields

= 2.2.4 =
*Envios costo cero reemplazados por 'GRATIS'

= 2.2.3 =
*corrección calculo variaciones de tamaño unitario

= 2.2.2 =
*corrección tarificador de variaciones

= 2.2.1 =
*Se solventó un posible problema de bloqueo de ip

= 2.2.0 =
*Se cambiaron los estados de los pedidos y se solucionaron los problemas con los pedidos sin estados

= 2.1.1 =
*los pedidos que no se encuentren completados o en proceso no serán enviados

= 2.0.1 =
*se corrigen problemas con algunas imágenes

= 2.0 =
*Se crearon configuraciones de envío
*costos personalizados
*comunas especificas
*envíos automáticos
*validadores de respuesta
*desabilitar couriers
*medidas estandar
*soporte a diferentes unidades de medidas
*corrección a zonas de envios

= 1.1.1 =
Upgrade notices describe the reason a user should upgrade.  No more than 300 characters.


