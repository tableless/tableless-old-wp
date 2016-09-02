<?php

$chart = array();

if ( 0 != $stats['unoptimized-library-size'] ) {
	$chart['percentage'] = round( 100 - ( $stats['optimized-library-size'] / $stats['unoptimized-library-size'] * 100 ), 1 );
} else {
	$chart['percentage'] = 0;
}

$chart['size'] = 180;
$chart['radius'] = $chart['size'] / 2 * 0.9;
$chart['main-radius'] = $chart['radius'] * 0.88;
$chart['center'] = $chart['size'] / 2;
$chart['stroke'] = $chart['radius'] / 2;
$chart['dash-stroke'] = $chart['radius'] / 4;
$chart['inner-radius'] = $chart['radius'] - $chart['stroke'] / 2;
$chart['circle-size'] = 2 * pi() * $chart['main-radius'];
$chart['dash-array-size'] = $chart['percentage'] / 100 * $chart['circle-size'];

?>
<style>

div.savings div.chart svg circle.main {
	stroke-width: <?php echo $chart['dash-stroke'] ?>;
	stroke-dasharray: <?php echo $chart['dash-array-size'] . ' ' . $chart['circle-size'] ?>;
}

div.tiny-bulk-optimization div.savings div.chart div.value {
	min-width: <?php echo $chart['size'] ?>px;
}

@keyframes shwoosh {
	from {
		stroke-dasharray: <?php echo '0' . ' ' . $chart['circle-size'] ?>
	}
	to {
		stroke-dasharray: <?php echo $chart['dash-array-size'] . ' ' . $chart['circle-size'] ?>
	}
}

</style>

<div id="optimization-chart" class="chart" data-full-circle-size="<?php echo $chart['circle-size'] ?>" data-percentage-factor="<?php echo $chart['main-radius'] ?>" >
	<svg width="<?php echo $chart['size'] ?>" height="<?php echo $chart['size'] ?>">
		<circle class="main" transform="rotate(-90, <?php echo $chart['center'] ?>, <?php echo $chart['center'] ?>)" r="<?php echo $chart['main-radius'] ?>" cx="<?php echo $chart['center'] ?>" cy="<?php echo $chart['center'] ?>"/>
		<circle class="inner" r="<?php echo $chart['inner-radius'] ?>" cx="<?php echo $chart['center'] ?>" cy="<?php echo $chart['center'] ?>" />
	</svg>
	<div class="value">
		<div class="percentage" id="savings-percentage"><?php echo $chart['percentage'] ?>%</div>
		<div class="label" ><?php echo esc_html__( 'savings', 'tiny-compress-images' ); ?></div>
	</div>
</div>
