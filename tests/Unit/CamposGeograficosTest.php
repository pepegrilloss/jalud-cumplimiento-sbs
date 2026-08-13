<?php

namespace Tests\Unit;

use App\Filament\Forms\CamposGeograficos;
use Filament\Forms\Components\Select;
use Tests\TestCase;

class CamposGeograficosTest extends TestCase
{
    public function test_construye_selectores_encadenados_para_un_domicilio(): void
    {
        $campos = CamposGeograficos::domicilio(
            'DomicilioDepartamento',
            'DomicilioProvincia',
            'DomicilioDistrito',
        );

        $this->assertSame(
            ['DomicilioDepartamento', 'DomicilioProvincia', 'DomicilioDistrito'],
            array_map(fn (Select $campo): string => $campo->getName(), $campos),
        );

        foreach ($campos as $campo) {
            $this->assertInstanceOf(Select::class, $campo);
        }
    }
}
