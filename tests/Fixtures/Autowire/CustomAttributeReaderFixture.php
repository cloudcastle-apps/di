<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Fixture для чтения зарегистрированного пользовательского attribute.
 */
final class CustomAttributeReaderFixture
{
    #[CustomServiceIdAttribute(service: 'mailer')]
    public string $dependency = '';
}
