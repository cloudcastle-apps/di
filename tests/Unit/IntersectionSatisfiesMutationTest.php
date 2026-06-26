<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use ArrayIterator;
use CloudCastle\DI\ClassDependencyResolver;
use CloudCastle\DI\Container;
use CloudCastle\DI\IntersectionTypeResolver;
use CloudCastle\DI\Tests\Fixtures\Autowire\PsrCountableStub;
use Countable;
use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionMethod;

/**
 * Mutation-тесты для {@see IntersectionTypeResolver::satisfiesIntersection()}.
 */
#[CoversClass(IntersectionTypeResolver::class)]
final class IntersectionSatisfiesMutationTest extends TestCase
{
    public function testSatisfiesIntersectionAcceptsPsrContainerWithCountable(): void
    {
        $psrContainer = new PsrCountableStub();

        $resolver = new IntersectionTypeResolver(new ClassDependencyResolver(new Container()));
        $method = new ReflectionMethod(IntersectionTypeResolver::class, 'satisfiesIntersection');

        self::assertTrue($method->invoke($resolver, $psrContainer, [
            PsrContainerInterface::class,
            Countable::class,
        ]));
    }

    public function testSatisfiesIntersectionRejectsNonObjectCandidate(): void
    {
        $resolver = new IntersectionTypeResolver(new ClassDependencyResolver(new Container()));
        $method = new ReflectionMethod(IntersectionTypeResolver::class, 'satisfiesIntersection');

        self::assertFalse($method->invoke($resolver, 'not-an-object', [Iterator::class]));
    }

    public function testSatisfiesIntersectionRejectsCandidateMissingPsrContainerInterface(): void
    {
        $storage = new ArrayIterator(['value']);

        $resolver = new IntersectionTypeResolver(new ClassDependencyResolver(new Container()));
        $method = new ReflectionMethod(IntersectionTypeResolver::class, 'satisfiesIntersection');

        self::assertFalse($method->invoke($resolver, $storage, [
            PsrContainerInterface::class,
            Iterator::class,
        ]));
    }
}
