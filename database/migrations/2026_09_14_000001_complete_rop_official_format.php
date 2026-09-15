<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rop_registros', function (Blueprint $table): void {
            $table->json('DatosRegulatorios')->nullable()->after('SnapshotDatos');
        });

        Schema::create('rop_catalogo_items', function (Blueprint $table): void {
            $table->bigIncrements('RopCatalogoItemID');
            $table->string('Tipo', 50)->index();
            $table->string('Codigo', 80);
            $table->string('Nombre', 500);
            $table->string('Grupo', 180)->nullable();
            $table->boolean('Activo')->default(true)->index();
            $table->unsignedInteger('Orden')->default(0);
            $table->timestamps();
            $table->unique(['Tipo', 'Codigo'], 'uq_rop_catalogo_tipo_codigo');
        });

        $items = [];
        $add = function (string $tipo, string $nombre, int $orden, ?string $grupo = null) use (&$items): void {
            $items[] = [
                'Tipo' => $tipo,
                'Codigo' => Str::upper(Str::limit(Str::ascii(Str::slug($nombre, '_')), 70, '')).sprintf('_%03d', $orden),
                'Nombre' => $nombre,
                'Grupo' => $grupo,
                'Activo' => true,
                'Orden' => $orden,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        };

        foreach ([
            'Agencias de Aduana',
            'Despachadores de operaciones de importación y/o exportación',
            'Comisión de Lucha contra los Delitos Aduaneros y la Piratería',
            'Persona natural o jurídica que se dedica al servicio de correo y/o courier, supervisada por el Ministerio de Transportes y Comunicaciones (MTC)',
            'Notario',
            'Persona jurídica que recibe donaciones o aportes de terceros (ONGD, ENIEX, IPREDA), supervisada por APCI o el Consejo de Supervigilancia de Fundaciones',
            'Persona natural o jurídica dedicada a la compraventa de aeronaves',
            'Persona natural o jurídica dedicada a la compraventa de embarcaciones',
            'Laboratorios y empresas que producen y/o comercializan insumos químicos que se utilicen para la fabricación de drogas y/o explosivos',
            'Persona natural o jurídica dedicada a la compraventa y/o importación de armas',
            'Persona natural o jurídica dedicada a la fabricación de materiales explosivos',
            'Persona natural o jurídica dedicada a comercialización de materiales explosivos',
            'Organización o institución pública receptora de fondos que no provengan del erario nacional',
            'Empresa minera', 'Agencia de Viaje y Turismo', 'Hotel', 'Servicio de Caja(s) de Seguridad',
            'Empresa que permita que mediante sus programas y sistemas de informática se realicen operaciones sospechosas',
        ] as $index => $nombre) {
            $add('TIPO_SUJETO_OBLIGADO', $nombre, $index + 1);
        }

        $actividades = [
            'Agropecuario' => ['Agrícola', 'Pecuario', 'Silvicultura (extracción de madera)'],
            'Pesca' => ['Pesca'],
            'Minería e Hidrocarburos' => ['Minería', 'Hidrocarburos y/o similares'],
            'Manufactura - Procesadores de Recursos Primarios' => ['Conservas y Productos de Pescado', 'Refinación de Petróleo', 'Productos Cárnicos', 'Azúcar', 'Arroz', 'Otras Industrias Primarias'],
            'Manufactura - Industria No Primaria' => ['Alimentos, Bebidas y Tabaco', 'Textil, Cuero y Calzado', 'Transformación de la Madera', 'Industrias del Papel, Imprenta y Reproducción de Grabaciones', 'Productos Químicos, Caucho y Plástico', 'Minerales no Metálicos', 'Productos Metálicos, Maquinaria e Equipo', 'Industria del Hierro y Acero', 'Reciclaje', 'Otras Industrias'],
            'Servicios' => ['Generación de Energía Eléctrica y Agua', 'Restaurantes', 'Hoteles, Hostales y Alojamientos', 'Casinos, Tragamonedas, Loterías, Juegos de Azar y/o similares', 'Transportes', 'Almacenamiento', 'Telecomunicaciones y Correos', 'Intermediación Financiera', 'Compraventa de divisas', 'Administración Pública, Defensa y Seguridad Social (PEPS)', 'Enseñanza', 'Salud', 'Informática', 'Asesoramiento, Consultoría e Investigación y Desarrollo', 'Otros Servicios'],
            'Construcción' => ['Construcción', 'Inmobiliaria', 'Alquiler de Maquinaria'],
            'Comercio' => ['Comercio Automotriz', 'Comercio de Arroz', 'Comercio al por mayor', 'Comercio al por menor', 'Otros Comercios'],
            'Asociaciones y Organizaciones Extraterritoriales' => ['Actividades de Asociaciones', 'ONG', 'Organizaciones y Órganos Extraterritoriales'],
            'No identificado' => ['No identificado'],
        ];
        $orden = 1;
        foreach ($actividades as $sector => $nombres) {
            foreach ($nombres as $nombre) {
                $add('ACTIVIDAD_ECONOMICA', $nombre, $orden++, $sector);
            }
        }

        foreach (['Alameda', 'Avenida', 'Block', 'Calle', 'Carretera', 'Jirón', 'Malecón', 'Óvalo', 'Parque', 'Pasaje', 'Plaza', 'Prolongación'] as $index => $nombre) {
            $add('TIPO_VIA', $nombre, $index + 1);
        }

        DB::table('rop_catalogo_items')->insert($items);
    }

    public function down(): void
    {
        Schema::dropIfExists('rop_catalogo_items');
        Schema::table('rop_registros', fn (Blueprint $table) => $table->dropColumn('DatosRegulatorios'));
    }
};
