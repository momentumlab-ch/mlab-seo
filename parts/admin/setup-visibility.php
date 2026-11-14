<?php
/**
 * Quick setup, step 3: AI crawlers and IndexNow.
 *
 * @package MomentumLab\SEO
 * @var array $s   Settings.
 * @var bool  $pro Pro active.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

Admin::part( 'ai-crawlers' );
Admin::part( 'ai-indexnow' );
