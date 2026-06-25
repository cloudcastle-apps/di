<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use CloudCastle\DI\Attribute\Autowire;
use CloudCastle\DI\Attribute\Inject;

/**
 * Holder для тестов {@see AttributeServiceIdReader}.
 */
final class AttributeReaderFixtures
{
    #[Autowire(service: 'mailer')]
    public string $withAutowireService = '';

    #[Inject]
    public string $withInjectWithoutId = '';

    #[UnrelatedAttribute]
    public string $unrelated = '';
}
