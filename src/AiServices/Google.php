<?php

declare(strict_types=1);

namespace MageOS\AiBase\AiServices;

use MageOS\AiBase\Api\Data\AiServiceConfigurationInterface;
use MageOS\AiBase\Api\Data\FieldDescriptorInterfaceFactory;

class Google implements AiServiceConfigurationInterface
{
    use FieldFactoryTrait;

    /**
     * @param FieldDescriptorInterfaceFactory $fieldFactory
     */
    public function __construct(
        private readonly FieldDescriptorInterfaceFactory $fieldFactory,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getCode(): string
    {
        return 'google';
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Google Gemini';
    }

    /**
     * @inheritdoc
     */
    public function getSupportedModels(): array
    {
        return [
            'gemini-3.8-flash'       => 'Gemini 3.8 Flash',
            'gemini-3.7-flash'       => 'Gemini 3.7 Flash',
            'gemini-3.5-flash-lite'  => 'Gemini 3.5 Flash-Lite',
            'gemini-3.1-pro-preview' => 'Gemini 3.1 Pro (Preview)',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getConfigurationFields(): array
    {
        return [
            $this->apiKeyField($this->fieldFactory),
            $this->modelField($this->fieldFactory, $this->getSupportedModels()),
        ];
    }
}
