<?php

namespace App\Filament\Forms;

use App\Services\Geografia\CatalogoGeograficoService;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;

class CamposGeograficos
{
    public static function pais(string $campo, string $label = 'País'): Select
    {
        return Select::make($campo)
            ->label($label)
            ->options(fn (Get $get): array => self::conservarActual(
                app(CatalogoGeograficoService::class)->paises(),
                $get($campo),
            ))
            ->searchable()
            ->preload()
            ->native(false);
    }

    /** @return array{Select, Select, Select} */
    public static function domicilio(
        string $departamento,
        string $provincia,
        string $distrito,
    ): array {
        return [
            Select::make($departamento)
                ->label('Departamento')
                ->options(fn (Get $get): array => self::conservarActual(
                    app(CatalogoGeograficoService::class)->departamentos(),
                    $get($departamento),
                ))
                ->searchable()
                ->preload()
                ->native(false)
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set) use ($provincia, $distrito): void {
                    $set($provincia, null);
                    $set($distrito, null);
                }),
            Select::make($provincia)
                ->label('Provincia')
                ->options(fn (Get $get): array => self::conservarActual(
                    app(CatalogoGeograficoService::class)->provincias($get($departamento)),
                    $get($provincia),
                ))
                ->searchable()
                ->preload()
                ->native(false)
                ->required()
                ->disabled(fn (Get $get): bool => blank($get($departamento)))
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set($distrito, null)),
            Select::make($distrito)
                ->label('Distrito')
                ->options(fn (Get $get): array => self::conservarActual(
                    app(CatalogoGeograficoService::class)->distritos($get($departamento), $get($provincia)),
                    $get($distrito),
                ))
                ->searchable()
                ->preload()
                ->native(false)
                ->required()
                ->disabled(fn (Get $get): bool => blank($get($provincia))),
        ];
    }

    /** @param array<string, string> $opciones
     * @return array<string, string>
     */
    private static function conservarActual(array $opciones, mixed $actual): array
    {
        $actual = trim((string) $actual);

        if ($actual !== '' && ! array_key_exists($actual, $opciones)) {
            $opciones[$actual] = $actual;
        }

        return $opciones;
    }
}
