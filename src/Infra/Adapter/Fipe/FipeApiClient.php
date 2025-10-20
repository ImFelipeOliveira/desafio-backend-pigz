<?php

declare(strict_types=1);

namespace App\Infra\Adapter\Fipe;

use App\Domain\Services\FipeServiceInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Adapter para integração com a API FIPE
 * Documentação: https://deividfortuna.github.io/fipe/
 */
class FipeApiClient implements FipeServiceInterface
{
  private const BASE_URL = 'https://parallelum.com.br/fipe/api/v1';
  private const TIMEOUT = 10;

  public function __construct(
    private readonly HttpClientInterface $httpClient,
    private readonly LoggerInterface $logger
  ) {}

  public function getBrands(string $vehicleType = 'carros'): array
  {
    try {
      $url = sprintf('%s/%s/marcas', self::BASE_URL, $vehicleType);
      $response = $this->httpClient->request('GET', $url, [
        'timeout' => self::TIMEOUT,
      ]);

      if ($response->getStatusCode() !== 200) {
        $this->logger->error('FIPE API error getting brands', [
          'status' => $response->getStatusCode(),
          'vehicle_type' => $vehicleType,
        ]);
        return [];
      }

      return $response->toArray();
    } catch (TransportExceptionInterface $e) {
      $this->logger->error('FIPE API transport error getting brands', [
        'error' => $e->getMessage(),
        'vehicle_type' => $vehicleType,
      ]);
      return [];
    } catch (\Exception $e) {
      $this->logger->error('Unexpected error getting brands from FIPE', [
        'error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  public function getModels(string $vehicleType, string $brandCode): array
  {
    try {
      $url = sprintf('%s/%s/marcas/%s/modelos', self::BASE_URL, $vehicleType, $brandCode);
      $response = $this->httpClient->request('GET', $url, [
        'timeout' => self::TIMEOUT,
      ]);

      if ($response->getStatusCode() !== 200) {
        $this->logger->error('FIPE API error getting models', [
          'status' => $response->getStatusCode(),
          'brand_code' => $brandCode,
        ]);
        return [];
      }

      $data = $response->toArray();
      return $data['modelos'] ?? [];
    } catch (TransportExceptionInterface $e) {
      $this->logger->error('FIPE API transport error getting models', [
        'error' => $e->getMessage(),
        'brand_code' => $brandCode,
      ]);
      return [];
    } catch (\Exception $e) {
      $this->logger->error('Unexpected error getting models from FIPE', [
        'error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  public function getYears(string $vehicleType, string $brandCode, string $modelCode): array
  {
    try {
      $url = sprintf(
        '%s/%s/marcas/%s/modelos/%s/anos',
        self::BASE_URL,
        $vehicleType,
        $brandCode,
        $modelCode
      );

      $response = $this->httpClient->request('GET', $url, [
        'timeout' => self::TIMEOUT,
      ]);

      if ($response->getStatusCode() !== 200) {
        $this->logger->error('FIPE API error getting years', [
          'status' => $response->getStatusCode(),
          'model_code' => $modelCode,
        ]);
        return [];
      }

      return $response->toArray();
    } catch (TransportExceptionInterface $e) {
      $this->logger->error('FIPE API transport error getting years', [
        'error' => $e->getMessage(),
        'model_code' => $modelCode,
      ]);
      return [];
    } catch (\Exception $e) {
      $this->logger->error('Unexpected error getting years from FIPE', [
        'error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  public function getVehicleValue(
    string $vehicleType,
    string $brandCode,
    string $modelCode,
    string $yearCode
  ): array {
    try {
      $url = sprintf(
        '%s/%s/marcas/%s/modelos/%s/anos/%s',
        self::BASE_URL,
        $vehicleType,
        $brandCode,
        $modelCode,
        $yearCode
      );

      $response = $this->httpClient->request('GET', $url, [
        'timeout' => self::TIMEOUT,
      ]);

      if ($response->getStatusCode() !== 200) {
        $this->logger->error('FIPE API error getting vehicle value', [
          'status' => $response->getStatusCode(),
          'year_code' => $yearCode,
        ]);
        return [];
      }

      return $response->toArray();
    } catch (TransportExceptionInterface $e) {
      $this->logger->error('FIPE API transport error getting vehicle value', [
        'error' => $e->getMessage(),
        'year_code' => $yearCode,
      ]);
      return [];
    } catch (\Exception $e) {
      $this->logger->error('Unexpected error getting vehicle value from FIPE', [
        'error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  public function getValueByFipeCode(string $fipeCode, string $vehicleType = 'carros'): ?array
  {
    // A API FIPE não tem endpoint direto por código FIPE
    // Precisaríamos fazer busca por marca/modelo/ano
    // Por ora, retornamos null e logamos
    $this->logger->warning('getValueByFipeCode not implemented - FIPE API limitation', [
      'fipe_code' => $fipeCode,
    ]);

    return null;
  }

  /**
   * Helper para converter string de preço FIPE (ex: "R$ 50.000,00") para float
   */
  public static function parseFipePrice(string $priceString): float
  {
    // Remove "R$", espaços, pontos de milhar e troca vírgula por ponto
    $cleaned = str_replace(['R$', '.', ' '], '', $priceString);
    $cleaned = str_replace(',', '.', $cleaned);

    return (float) $cleaned;
  }

  /**
   * Helper para extrair código FIPE da resposta da API
   */
  public static function extractFipeCode(array $vehicleData): ?string
  {
    return $vehicleData['CodigoFipe'] ?? $vehicleData['codigoFipe'] ?? null;
  }
}
