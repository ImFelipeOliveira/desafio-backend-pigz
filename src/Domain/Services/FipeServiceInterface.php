<?php

declare(strict_types=1);

namespace App\Domain\Services;

/**
 * Interface para integração com API FIPE
 * Permite buscar informações de veículos da tabela FIPE
 */
interface FipeServiceInterface
{
  /**
   * Busca todas as marcas de um tipo de veículo
   *
   * @param string $vehicleType Tipo: 'carros', 'motos' ou 'caminhoes'
   * @return array Array de marcas [['codigo' => string, 'nome' => string], ...]
   */
  public function getBrands(string $vehicleType = 'carros'): array;

  /**
   * Busca todos os modelos de uma marca
   *
   * @param string $vehicleType Tipo: 'carros', 'motos' ou 'caminhoes'
   * @param string $brandCode Código da marca
   * @return array Array de modelos [['codigo' => string, 'nome' => string], ...]
   */
  public function getModels(string $vehicleType, string $brandCode): array;

  /**
   * Busca todos os anos disponíveis de um modelo
   *
   * @param string $vehicleType Tipo do veículo
   * @param string $brandCode Código da marca
   * @param string $modelCode Código do modelo
   * @return array Array de anos [['codigo' => string, 'nome' => string], ...]
   */
  public function getYears(string $vehicleType, string $brandCode, string $modelCode): array;

  /**
   * Busca o preço e informações detalhadas de um veículo específico
   *
   * @param string $vehicleType Tipo do veículo
   * @param string $brandCode Código da marca
   * @param string $modelCode Código do modelo
   * @param string $yearCode Código do ano (ex: '2020-1' para gasolina)
   * @return array Dados do veículo incluindo preço FIPE
   */
  public function getVehicleValue(
    string $vehicleType,
    string $brandCode,
    string $modelCode,
    string $yearCode
  ): array;

  /**
   * Busca o preço FIPE usando o código FIPE direto
   *
   * @param string $fipeCode Código FIPE do veículo
   * @param string $vehicleType Tipo do veículo
   * @return array|null Dados do veículo ou null se não encontrado
   */
  public function getValueByFipeCode(string $fipeCode, string $vehicleType = 'carros'): ?array;
}
