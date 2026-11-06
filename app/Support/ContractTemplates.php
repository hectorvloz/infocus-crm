<?php

namespace App\Support;

/**
 * Plantillas de contrato para un estudio creativo en Colombia.
 *
 * Son modelos de referencia: los datos de Indigo salen de Ajustes y los del cliente se
 * completan en el editor ([CLIENTE], [NIT CLIENTE], [REPRESENTANTE CLIENTE], [CIUDAD CLIENTE],
 * [VALOR], [FECHA]). Lo demás entre corchetes se llena a mano. No reemplazan la revisión de un abogado.
 */
class ContractTemplates
{
    public static function all(array $settings = []): array
    {
        $empresa = e(trim((string) ($settings['company_name'] ?? '')) ?: 'Estudio Indigo');
        $taxLabel = e(trim((string) ($settings['company_tax_label'] ?? '')) ?: 'NIT');
        $taxId = e(trim((string) ($settings['company_tax_id'] ?? '')) ?: '[NIT DEL ESTUDIO]');
        $ciudad = e(trim((string) ($settings['company_city'] ?? '')) ?: '[CIUDAD]');

        $partes = "<p>Entre los suscritos, <strong>{$empresa}</strong>, identificado con {$taxLabel} <strong>{$taxId}</strong>, con domicilio en {$ciudad}, en adelante <strong>EL CONTRATISTA</strong>, y <strong>[CLIENTE]</strong>, identificado con NIT o cédula <strong>[NIT CLIENTE]</strong>, representado por <strong>[REPRESENTANTE CLIENTE]</strong>, con domicilio en [CIUDAD CLIENTE], en adelante <strong>EL CONTRATANTE</strong>, hemos acordado celebrar el presente contrato, que se regirá por las siguientes cláusulas:</p>";

        // Los bloques de firma con su evidencia los agrega el PDF; aquí solo va la constancia.
        $firmas = "<p>En constancia se firma en {$ciudad}, el [FECHA]. Las partes aceptan la firma electrónica de este documento conforme a la Ley 527 de 1999 y el Decreto 2364 de 2012.</p>";

        $independencia = "<h2>Autonomía e independencia</h2><p>EL CONTRATISTA ejecutará el objeto con autonomía técnica, administrativa y financiera, con sus propios medios y personal. Este contrato es de naturaleza civil y comercial (artículos 2063 y siguientes del Código Civil) y no genera relación laboral entre las partes ni con el personal de cada una. Cada parte responde por los salarios, prestaciones y aportes a seguridad social de su propio personal.</p>";

        $datos = "<h2>Protección de datos personales</h2><p>Las partes tratarán los datos personales que conozcan con ocasión de este contrato conforme a la Ley 1581 de 2012 y el Decreto 1074 de 2015, solo para las finalidades del contrato y con las medidas de seguridad necesarias. Cuando EL CONTRATISTA trate datos de clientes o usuarios de EL CONTRATANTE, actuará como encargado del tratamiento y seguirá sus instrucciones.</p>";

        $controversias = "<h2>Solución de controversias</h2><p>Las diferencias se resolverán primero por arreglo directo dentro de los quince (15) días hábiles siguientes a la notificación escrita de una parte a la otra. Si no hay acuerdo, podrán acudir a la conciliación en un centro autorizado o a los jueces competentes de {$ciudad}.</p>";

        $notificaciones = "<h2>Notificaciones</h2><p>Las comunicaciones se enviarán por escrito a los correos electrónicos de las partes: EL CONTRATISTA en [CORREO DEL ESTUDIO] y EL CONTRATANTE en [CORREO CLIENTE]. Cualquier cambio de dirección se informará por escrito.</p>";

        $merito = "<h2>Mérito ejecutivo</h2><p>Este documento presta mérito ejecutivo para el cobro de las sumas adeudadas, por contener obligaciones claras, expresas y exigibles en los términos del artículo 422 del Código General del Proceso.</p>";

        return [
            'servicios' => [
                'titulo' => 'Contrato de prestación de servicios profesionales',
                'descripcion' => 'Servicios creativos en general: alcance, pagos, revisiones, derechos de autor y terminación.',
                'contenido' => "<h1>Contrato de prestación de servicios profesionales</h1>{$partes}"
                    . "<h2>Primera. Objeto</h2><p>EL CONTRATISTA prestará a EL CONTRATANTE los servicios de [DESCRIPCIÓN DEL SERVICIO], de acuerdo con la propuesta comercial [NÚMERO O FECHA DE LA PROPUESTA], que hace parte integral de este contrato.</p>"
                    . "<h2>Segunda. Alcance y entregables</h2><p>Los entregables son:</p><ol><li>[ENTREGABLE 1]</li><li>[ENTREGABLE 2]</li><li>[ENTREGABLE 3]</li></ol><p>Cualquier trabajo adicional no incluido en esta cláusula se cotizará por separado y requerirá aprobación escrita de EL CONTRATANTE.</p>"
                    . "<h2>Tercera. Plazo</h2><p>El contrato tendrá una duración de [PLAZO] contados desde el pago del anticipo y la entrega completa de la información y materiales por parte de EL CONTRATANTE. Los retrasos en la entrega de información, contenidos o aprobaciones de EL CONTRATANTE amplían el plazo en el mismo tiempo.</p>"
                    . "<h2>Cuarta. Valor y forma de pago</h2><p>El valor total del contrato es de <strong>[VALOR]</strong>, más los impuestos que apliquen, pagadero así: (a) cincuenta por ciento (50 %) como anticipo a la firma del contrato, y (b) cincuenta por ciento (50 %) contra la entrega final. Los pagos se harán dentro de los [DÍAS] días siguientes a la presentación de la factura electrónica o cuenta de cobro, según corresponda, a la cuenta que indique EL CONTRATISTA. EL CONTRATANTE podrá practicar las retenciones de ley.</p>"
                    . "<h2>Quinta. Revisiones y aprobaciones</h2><p>El precio incluye [NÚMERO] rondas de ajustes por entregable. EL CONTRATANTE revisará cada entrega dentro de los cinco (5) días hábiles siguientes; si no presenta observaciones en ese plazo, la entrega se entenderá aprobada. Los ajustes adicionales o los cambios de alcance se cotizarán aparte.</p>"
                    . "<h2>Sexta. Obligaciones de EL CONTRATISTA</h2><ol><li>Ejecutar el objeto con calidad, oportunidad y conforme a las buenas prácticas de la profesión.</li><li>Informar el avance del trabajo y cualquier situación que afecte el cronograma.</li><li>Guardar la confidencialidad de la información de EL CONTRATANTE.</li></ol>"
                    . "<h2>Séptima. Obligaciones de EL CONTRATANTE</h2><ol><li>Pagar el valor acordado en las fechas pactadas.</li><li>Entregar a tiempo la información, contenidos, accesos y materiales necesarios, y garantizar que tiene derecho a usarlos.</li><li>Designar un interlocutor con capacidad para aprobar las entregas.</li></ol>"
                    . "<h2>Octava. Propiedad intelectual</h2><p>Una vez pagado el valor total del contrato, EL CONTRATISTA transfiere a EL CONTRATANTE los derechos patrimoniales de autor sobre las piezas finales aprobadas, para todo el mundo y por el término de protección legal, conforme a la Ley 23 de 1982 y la Decisión Andina 351 de 1993. Mientras exista saldo pendiente, EL CONTRATANTE no podrá usar las piezas. Los derechos morales del autor son irrenunciables. No se transfieren las propuestas descartadas, los archivos de trabajo intermedios, ni las herramientas, tipografías, imágenes o licencias de terceros, cuyo uso se rige por sus propias licencias. EL CONTRATISTA podrá mostrar el trabajo en su portafolio y redes, salvo que EL CONTRATANTE lo prohíba por escrito.</p>"
                    . "<h2>Novena. Confidencialidad</h2><p>Las partes mantendrán en reserva la información confidencial que reciban con ocasión de este contrato y solo la usarán para ejecutarlo. Esta obligación se mantiene durante dos (2) años después de terminado el contrato.</p>"
                    . $independencia . $datos
                    . "<h2>Terminación</h2><p>El contrato terminará por cumplimiento del objeto, por mutuo acuerdo o por incumplimiento grave de una de las partes, previa notificación escrita con diez (10) días hábiles para subsanarlo. Si EL CONTRATANTE termina el contrato sin justa causa, pagará el trabajo realizado hasta la fecha, y el anticipo no será reembolsable.</p>"
                    . $controversias . $notificaciones . $merito . $firmas,
            ],
            'sitio_web' => [
                'titulo' => 'Contrato de diseño y desarrollo de sitio web',
                'descripcion' => 'Sitios, landings y tiendas: hosting, dominio, contenidos, garantía y entrega de accesos.',
                'contenido' => "<h1>Contrato de diseño y desarrollo de sitio web</h1>{$partes}"
                    . "<h2>Primera. Objeto</h2><p>EL CONTRATISTA diseñará y desarrollará para EL CONTRATANTE un sitio web tipo [LANDING / PÁGINA EMPRESARIAL / TIENDA VIRTUAL], en la dirección [DOMINIO], según la propuesta [NÚMERO O FECHA DE LA PROPUESTA], que hace parte integral de este contrato.</p>"
                    . "<h2>Segunda. Alcance</h2><ol><li>Número de páginas o secciones: [NÚMERO].</li><li>Funcionalidades: [FORMULARIOS, BLOG, PAGOS EN LÍNEA, INTEGRACIONES].</li><li>Diseño adaptable a celulares, tabletas y computadores.</li><li>Configuración básica de SEO y analítica.</li><li>Capacitación de [NÚMERO] hora(s) para administrar el sitio.</li></ol><p>Los textos, fotografías, logos y demás contenidos los entrega EL CONTRATANTE, salvo que la propuesta diga otra cosa.</p>"
                    . "<h2>Tercera. Etapas y plazo</h2><p>El proyecto se desarrolla en estas etapas: (1) descubrimiento y estructura, (2) diseño, (3) desarrollo, (4) pruebas y (5) publicación. El plazo estimado es de [PLAZO] desde el pago del anticipo y la entrega de los contenidos. Cada etapa requiere la aprobación de EL CONTRATANTE para continuar.</p>"
                    . "<h2>Cuarta. Valor y forma de pago</h2><p>El valor total es de <strong>[VALOR]</strong>, más los impuestos que apliquen, pagadero así: cincuenta por ciento (50 %) de anticipo a la firma y cincuenta por ciento (50 %) antes de la publicación del sitio. Los pagos se harán contra factura electrónica o cuenta de cobro, según corresponda.</p>"
                    . "<h2>Quinta. Hosting, dominio y servicios de terceros</h2><p>El dominio y el hosting quedarán a nombre de EL CONTRATANTE. Su costo, y el de licencias, plantillas, plugins, pasarelas de pago u otros servicios de terceros, corre por cuenta de EL CONTRATANTE, salvo que la propuesta los incluya. EL CONTRATISTA no responde por fallas, cambios de precio o interrupciones de esos proveedores.</p>"
                    . "<h2>Sexta. Revisiones</h2><p>Se incluyen [NÚMERO] rondas de ajustes en la etapa de diseño y una (1) ronda de correcciones en la etapa de pruebas. Los cambios posteriores a la aprobación de una etapa, o nuevas funcionalidades, se cotizarán aparte.</p>"
                    . "<h2>Séptima. Garantía y soporte</h2><p>EL CONTRATISTA corregirá sin costo los errores de funcionamiento atribuibles a su desarrollo que se reporten dentro de los treinta (30) días siguientes a la publicación. La garantía no cubre cambios hechos por EL CONTRATANTE o terceros, actualizaciones de plataformas, ni fallas del hosting. El mantenimiento posterior se puede contratar por separado.</p>"
                    . "<h2>Octava. Entrega y accesos</h2><p>Con el pago total, EL CONTRATISTA entregará a EL CONTRATANTE las credenciales de administración del sitio, del hosting y del dominio que haya gestionado.</p>"
                    . "<h2>Novena. Propiedad intelectual</h2><p>Con el pago total, EL CONTRATANTE adquiere los derechos patrimoniales sobre el diseño y los desarrollos hechos a la medida para este proyecto, conforme a la Ley 23 de 1982 y la Decisión Andina 351 de 1993. Los componentes de código abierto, librerías, plantillas y licencias de terceros mantienen sus propias licencias. EL CONTRATISTA conserva el conocimiento técnico general y puede reutilizar componentes no exclusivos, y podrá mostrar el sitio en su portafolio.</p>"
                    . "<h2>Décima. Responsabilidad sobre contenidos</h2><p>EL CONTRATANTE es responsable de que los contenidos que entrega no infrinjan derechos de terceros y de cumplir las normas que le apliquen a su sitio, como la publicación de su política de tratamiento de datos (Ley 1581 de 2012) y, si vende en línea, las normas de protección al consumidor (Ley 1480 de 2011).</p>"
                    . $independencia . $datos
                    . "<h2>Terminación</h2><p>El contrato terminará por cumplimiento, mutuo acuerdo o incumplimiento grave no subsanado dentro de los diez (10) días hábiles siguientes a la notificación escrita. Si el proyecto se suspende por causas de EL CONTRATANTE por más de sesenta (60) días, EL CONTRATISTA podrá darlo por terminado y facturar lo ejecutado.</p>"
                    . $controversias . $notificaciones . $merito . $firmas,
            ],
            'branding' => [
                'titulo' => 'Contrato de diseño de identidad de marca',
                'descripcion' => 'Logo y branding: propuestas, cesión de derechos, manual de marca y registro ante la SIC.',
                'contenido' => "<h1>Contrato de diseño de identidad de marca</h1>{$partes}"
                    . "<h2>Primera. Objeto</h2><p>EL CONTRATISTA diseñará la identidad visual de la marca <strong>[NOMBRE DE LA MARCA]</strong> de EL CONTRATANTE, según la propuesta [NÚMERO O FECHA DE LA PROPUESTA].</p>"
                    . "<h2>Segunda. Entregables</h2><ol><li>[NÚMERO] propuestas conceptuales de logo.</li><li>Desarrollo de la propuesta elegida con sus versiones (principal, secundaria, ícono).</li><li>Paleta de color y tipografías.</li><li>Manual de marca en PDF.</li><li>Archivos finales en formatos vectoriales y de uso digital.</li></ol>"
                    . "<h2>Tercera. Proceso y revisiones</h2><p>EL CONTRATANTE elegirá una de las propuestas y tendrá [NÚMERO] rondas de ajustes sobre ella. Pedir propuestas nuevas después de elegir, o cambios de concepto, se cotizará aparte.</p>"
                    . "<h2>Cuarta. Plazo</h2><p>El trabajo se entregará en [PLAZO] desde el pago del anticipo y el diligenciamiento del cuestionario de marca. Las demoras en las aprobaciones de EL CONTRATANTE amplían el plazo en el mismo tiempo.</p>"
                    . "<h2>Quinta. Valor y forma de pago</h2><p>El valor total es de <strong>[VALOR]</strong>, más los impuestos que apliquen: cincuenta por ciento (50 %) de anticipo a la firma y cincuenta por ciento (50 %) contra la entrega de los archivos finales, contra factura electrónica o cuenta de cobro, según corresponda.</p>"
                    . "<h2>Sexta. Cesión de derechos patrimoniales</h2><p>Con el pago total, EL CONTRATISTA cede a EL CONTRATANTE, de forma exclusiva, para todo el mundo y por el término de protección legal, los derechos patrimoniales de autor sobre el diseño final aprobado (reproducción, comunicación pública, distribución y transformación), conforme a la Ley 23 de 1982, la Ley 1450 de 2011 y la Decisión Andina 351 de 1993. EL CONTRATANTE podrá inscribir esta cesión en la Dirección Nacional de Derecho de Autor. Las propuestas no elegidas siguen siendo de EL CONTRATISTA. Los derechos morales son irrenunciables. EL CONTRATISTA podrá mostrar el trabajo en su portafolio.</p>"
                    . "<h2>Séptima. Registro de marca</h2><p>El registro de la marca ante la Superintendencia de Industria y Comercio (Decisión Andina 486 de 2000) es responsabilidad de EL CONTRATANTE, salvo que se contrate aparte. EL CONTRATISTA hará el diseño de buena fe y sin copiar marcas conocidas, pero no garantiza que la marca sea registrable ni que no existan signos similares; se recomienda hacer un estudio de antecedentes antes de lanzarla.</p>"
                    . "<h2>Octava. Tipografías y recursos de terceros</h2><p>Las tipografías o recursos con licencia comercial que use la identidad deben ser licenciados por EL CONTRATANTE a su nombre. EL CONTRATISTA indicará cuáles son y dónde adquirirlos.</p>"
                    . "<h2>Novena. Confidencialidad</h2><p>Las partes guardarán reserva sobre la información estratégica y comercial que conozcan con ocasión de este contrato, durante su vigencia y dos (2) años después.</p>"
                    . $independencia . $datos
                    . "<h2>Terminación</h2><p>Si EL CONTRATANTE termina el contrato antes de la entrega final, pagará el trabajo realizado y el anticipo no será reembolsable. En ese caso no se ceden derechos sobre las propuestas.</p>"
                    . $controversias . $notificaciones . $merito . $firmas,
            ],
            'mantenimiento' => [
                'titulo' => 'Contrato de mantenimiento y soporte mensual',
                'descripcion' => 'Plan mensual: horas incluidas, tiempos de respuesta, renovación y terminación.',
                'contenido' => "<h1>Contrato de mantenimiento y soporte mensual</h1>{$partes}"
                    . "<h2>Primera. Objeto</h2><p>EL CONTRATISTA prestará servicios mensuales de mantenimiento, soporte y actualización de [SITIO WEB / PLATAFORMA / REDES], ubicado en [DOMINIO O CUENTA].</p>"
                    . "<h2>Segunda. Servicios incluidos</h2><ol><li>Hasta [NÚMERO] horas mensuales de cambios y actualizaciones de contenido.</li><li>Actualizaciones de seguridad de la plataforma y sus extensiones.</li><li>Copias de seguridad [SEMANALES / MENSUALES].</li><li>Monitoreo de disponibilidad.</li><li>Informe mensual de actividades.</li></ol><p>Las horas no usadas en un mes no se acumulan. Los trabajos que superen las horas incluidas se cobrarán a [VALOR HORA ADICIONAL] por hora, previa aprobación.</p>"
                    . "<h2>Tercera. Tiempos de respuesta</h2><p>Las solicitudes se atienden en días hábiles, de [HORARIO]. Las fallas críticas que dejen el sitio fuera de servicio tendrán respuesta en máximo [HORAS] horas hábiles; las demás solicitudes, en máximo [DÍAS] días hábiles.</p>"
                    . "<h2>Cuarta. Valor y forma de pago</h2><p>EL CONTRATANTE pagará una cuota mensual de <strong>[VALOR]</strong>, más los impuestos que apliquen, por mes anticipado, dentro de los primeros [DÍAS] días de cada mes, contra factura electrónica o cuenta de cobro, según corresponda. Si hay mora de más de treinta (30) días, EL CONTRATISTA podrá suspender el servicio hasta que se ponga al día.</p>"
                    . "<h2>Quinta. Vigencia y renovación</h2><p>El contrato tiene una duración de [MESES] meses desde el [FECHA DE INICIO] y se renovará automáticamente por períodos iguales si ninguna de las partes avisa por escrito su intención de no renovarlo con treinta (30) días de anticipación. El valor podrá ajustarse en cada renovación, como máximo según la variación del IPC del año anterior.</p>"
                    . "<h2>Sexta. Exclusiones</h2><p>No incluye rediseños, funcionalidades nuevas, compra de licencias, hosting o dominio, ni la recuperación por fallas causadas por cambios hechos por terceros, salvo acuerdo escrito.</p>"
                    . "<h2>Séptima. Accesos</h2><p>EL CONTRATANTE dará a EL CONTRATISTA los accesos necesarios y podrá cambiarlos al terminar el contrato. EL CONTRATISTA usará las credenciales solo para prestar el servicio.</p>"
                    . $independencia . $datos
                    . "<h2>Terminación</h2><p>Cualquiera de las partes podrá terminar el contrato con treinta (30) días de aviso escrito, sin penalidad, pagando los servicios prestados hasta la fecha.</p>"
                    . $controversias . $notificaciones . $merito . $firmas,
            ],
            'nda' => [
                'titulo' => 'Acuerdo de confidencialidad (NDA)',
                'descripcion' => 'Bilateral: protege la información compartida antes y durante un proyecto.',
                'contenido' => "<h1>Acuerdo de confidencialidad</h1>"
                    . "<p>Entre <strong>{$empresa}</strong>, identificado con {$taxLabel} <strong>{$taxId}</strong>, y <strong>[CLIENTE]</strong>, identificado con NIT o cédula <strong>[NIT CLIENTE]</strong>, representado por <strong>[REPRESENTANTE CLIENTE]</strong>, en adelante conjuntamente LAS PARTES, se celebra el presente acuerdo de confidencialidad:</p>"
                    . "<h2>Primera. Objeto</h2><p>Proteger la información confidencial que LAS PARTES compartan con ocasión de [DESCRIPCIÓN DEL PROYECTO O NEGOCIACIÓN].</p>"
                    . "<h2>Segunda. Información confidencial</h2><p>Es toda información técnica, comercial, financiera, estratégica, de clientes, de diseño o de cualquier otra naturaleza que una parte entregue a la otra, por cualquier medio, incluidos los secretos empresariales en los términos de la Decisión Andina 486 de 2000 y la Ley 256 de 1996.</p>"
                    . "<h2>Tercera. Exclusiones</h2><p>No es confidencial la información que: (a) sea de dominio público sin culpa de quien la recibe; (b) ya conociera quien la recibe antes de su entrega; (c) se reciba legítimamente de un tercero sin deber de reserva; o (d) deba revelarse por orden de autoridad competente, caso en el cual se avisará a la otra parte.</p>"
                    . "<h2>Cuarta. Obligaciones</h2><ol><li>Usar la información solo para el objeto de este acuerdo.</li><li>No revelarla a terceros sin autorización escrita.</li><li>Compartirla solo con empleados o asesores que la necesiten y que estén obligados a guardar reserva.</li><li>Protegerla con medidas de seguridad razonables.</li><li>Devolverla o destruirla cuando la otra parte lo pida o al terminar el acuerdo.</li></ol>"
                    . "<h2>Quinta. Vigencia</h2><p>Este acuerdo rige desde su firma y la obligación de confidencialidad se mantiene durante [AÑOS] años después de su terminación, y por todo el tiempo en que la información conserve el carácter de secreto empresarial.</p>"
                    . "<h2>Sexta. Propiedad de la información</h2><p>La entrega de información no transfiere derechos de propiedad intelectual ni licencias sobre ella.</p>"
                    . "<h2>Séptima. Incumplimiento</h2><p>La parte que incumpla responderá por los perjuicios que cause, sin perjuicio de las acciones previstas en la Ley 256 de 1996 sobre competencia desleal.</p>"
                    . $datos . $controversias . $firmas,
            ],
            'cesion' => [
                'titulo' => 'Cesión de derechos patrimoniales de autor',
                'descripcion' => 'Documento independiente para transferir los derechos de una obra ya entregada.',
                'contenido' => "<h1>Contrato de cesión de derechos patrimoniales de autor</h1>"
                    . "<p>Entre <strong>{$empresa}</strong>, identificado con {$taxLabel} <strong>{$taxId}</strong>, en adelante <strong>EL CEDENTE</strong>, y <strong>[CLIENTE]</strong>, identificado con NIT o cédula <strong>[NIT CLIENTE]</strong>, representado por <strong>[REPRESENTANTE CLIENTE]</strong>, en adelante <strong>EL CESIONARIO</strong>, se celebra el presente contrato:</p>"
                    . "<h2>Primera. Obra</h2><p>EL CEDENTE es titular de los derechos patrimoniales sobre la obra <strong>[NOMBRE Y DESCRIPCIÓN DE LA OBRA]</strong>, creada para EL CESIONARIO y entregada el [FECHA DE ENTREGA].</p>"
                    . "<h2>Segunda. Derechos cedidos</h2><p>EL CEDENTE cede a EL CESIONARIO los derechos patrimoniales de reproducción, comunicación pública, distribución, transformación y puesta a disposición sobre la obra, en todas las modalidades de explotación y por cualquier medio conocido o por conocerse.</p>"
                    . "<h2>Tercera. Alcance</h2><p>La cesión es [EXCLUSIVA / NO EXCLUSIVA], para el territorio de [TODO EL MUNDO / PAÍS] y por [EL TÉRMINO DE PROTECCIÓN LEGAL / AÑOS].</p>"
                    . "<h2>Cuarta. Contraprestación</h2><p>El valor de la cesión es de <strong>[VALOR]</strong>, [INCLUIDO EN EL CONTRATO DE SERVICIOS DEL [FECHA] / PAGADERO A LA FIRMA].</p>"
                    . "<h2>Quinta. Derechos morales</h2><p>EL CEDENTE conserva los derechos morales sobre la obra, que son irrenunciables e inalienables (artículo 30 de la Ley 23 de 1982 y artículo 11 de la Decisión Andina 351 de 1993), y podrá incluirla en su portafolio salvo pacto en contrario.</p>"
                    . "<h2>Sexta. Garantía</h2><p>EL CEDENTE declara que la obra es original y que no ha cedido estos derechos a terceros. Los elementos de terceros incorporados (tipografías, imágenes, licencias) se rigen por sus propias licencias.</p>"
                    . "<h2>Séptima. Registro</h2><p>Esta cesión consta por escrito conforme al artículo 183 de la Ley 23 de 1982, modificado por la Ley 1450 de 2011. EL CESIONARIO podrá inscribirla en el Registro Nacional de Derecho de Autor de la Dirección Nacional de Derecho de Autor para que sea oponible a terceros.</p>"
                    . $controversias . $firmas,
            ],
        ];
    }

    /** Reemplaza los datos conocidos del cliente, el valor y la fecha; lo demás queda entre corchetes. */
    public static function fill(string $html, array $client, string $valor = ''): string
    {
        $values = [
            '[CLIENTE]' => $client['empresa'] ?? ($client['nombre'] ?? ''),
            '[NIT CLIENTE]' => $client['nit'] ?? '',
            '[REPRESENTANTE CLIENTE]' => $client['contacto_nombre'] ?? '',
            '[CIUDAD CLIENTE]' => $client['ciudad'] ?? '',
            '[CORREO CLIENTE]' => $client['contacto_email'] ?? ($client['email'] ?? ''),
            '[VALOR]' => $valor,
            '[FECHA]' => now()->setTimezone('America/Bogota')->locale('es')->translatedFormat('j \d\e F \d\e Y'),
        ];
        foreach ($values as $token => $value) {
            if (trim((string) $value) !== '') {
                $html = str_replace($token, e((string) $value), $html);
            }
        }

        return $html;
    }

    /** Datos entre corchetes que siguen sin llenar. */
    public static function pending(string $html): array
    {
        preg_match_all('/\[[A-ZÁÉÍÓÚÑ0-9][^\[\]<>]{1,80}\]/u', strip_tags($html), $m);

        return array_values(array_unique($m[0] ?? []));
    }
}
