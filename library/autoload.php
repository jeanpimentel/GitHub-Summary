<?php

require_once __DIR__ . '/symfony/src/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();

$loader->registerNamespaces(array(
    'Symfony' => __DIR__ . '/symfony/src',
    'Silex' => __DIR__ . '/silex/src',
));

$loader->registerPrefixes(array(
    'Pimple' => __DIR__ . '/pimple/lib',
    'Twig_' => __DIR__ . '/twig/lib',
));

$loader->register();
