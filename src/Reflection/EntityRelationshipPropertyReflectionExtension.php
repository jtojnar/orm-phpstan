<?php declare(strict_types = 1);

namespace Nextras\OrmPhpStan\Reflection;

use Nextras\Orm\Entity\IEntity;
use Nextras\OrmPhpStan\Reflection\Annotations\AnnotationPropertyReflection;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\IntegerType;
use PHPStan\Type\TypeCombinator;


class EntityRelationshipPropertyReflectionExtension implements PropertiesClassReflectionExtension
{
	/** @var AnnotationsPropertiesClassReflectionExtension */
	private $annotationsExtension;


	public function __construct(AnnotationsPropertiesClassReflectionExtension $annotationsExtension)
	{
		$this->annotationsExtension = $annotationsExtension;
	}


	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		$hasProperty = $this->annotationsExtension->hasProperty($classReflection, $propertyName);
		if (!$hasProperty) {
			return false;
		}

		$interfaces = array_map(function (ClassReflection $interface) {
			return $interface->getName();
		}, $classReflection->getInterfaces());
		if (!in_array(IEntity::class, $interfaces, true)) {
			return false;
		}

		$phpDoc = $classReflection->getNativeReflection()->getDocComment();
		if (!$phpDoc) {
			return false;
		}

		$hasRelationship = preg_match('#\$' . $propertyName . '\s(?:[^\n]*)\{[1m]:1.+\}.*$#m', $phpDoc) === 1;
		return $hasRelationship;
	}


	public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
	{
		$property = $this->annotationsExtension->getProperty($classReflection, $propertyName);
		return new AnnotationPropertyReflection(
			$property->getDeclaringClass(),
			$property->getReadableType(),
			TypeCombinator::union($property->getWritableType(), new IntegerType()),
			$property->isReadable(),
			$property->isWritable()
		);
	}
}
