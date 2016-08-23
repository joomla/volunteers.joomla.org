<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

JHtml::script('com_volunteers/markerclusterer.js', false, true);
?>

<div class="row-fluid">
	<img style="width: 100%" src="/images/volunteer-header.png">
</div>

<br>

<div class="row-fluid">
	<div class="span6">
		<h3><?php echo JText::_('COM_VOLUNTEERS_HOME_INTRO_HOW_TITLE'); ?></h3>
		<p><?php echo JText::_('COM_VOLUNTEERS_HOME_INTRO_HOW_DESC'); ?></p>
		<p><?php echo JText::_('COM_VOLUNTEERS_HOME_INTRO_HOW_ACTION'); ?></p>
		<p>
			<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=registration'); ?>" class="btn"><span class="icon-chevron-right"></span><?php echo JText::_('COM_VOLUNTEERS_HOME_INTRO_HOW_BUTTON'); ?>
			</a>
		</p>
	</div>
	<div class="span6">
		<h3><?php echo JText::_('COM_VOLUNTEERS_HOME_INTRO_WHY_TITLE'); ?></h3>
		<p><?php echo JText::_('COM_VOLUNTEERS_HOME_INTRO_WHY_DESC'); ?></p>
		<p><?php echo JText::_('COM_VOLUNTEERS_HOME_INTRO_WHY_ACTION'); ?></p>
		<p>
			<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=joomlers'); ?>" class="btn"><span class="icon-chevron-right"></span><?php echo JText::_('COM_VOLUNTEERS_HOME_INTRO_WHY_BUTTON'); ?>
			</a>
		</p>
	</div>
</div>

<br>

<div class="row-fluid">
	<div class="span8">
		<h2><?php echo JText::_('COM_VOLUNTEERS_LATEST_REPORTS') ?></h2>
		<?php if (!empty($this->reports)) foreach ($this->reports as $i => $item): ?>
			<div class="row-fluid report">
				<div class="span2">
					<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $item->volunteer_id) ?>">
						<?php echo VolunteersHelper::image($item->volunteer_image, 'large'); ?>
					</a>
				</div>
				<div class="span10">
					<h3 class="report-title">
						<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=report&id=' . $item->id) ?>">
							<?php echo($item->title); ?>
						</a>
					</h3>
					<p class="muted">
						<?php echo JText::_('COM_VOLUNTEERS_BY') ?>
						<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $item->volunteer_id) ?>"><?php echo $item->author_name; ?></a>
						<?php echo JText::_('COM_VOLUNTEERS_ON') ?> <?php echo VolunteersHelper::date($item->created, 'Y-m-d H:i'); ?>
						<?php echo JText::_('COM_VOLUNTEERS_IN') ?>
						<?php if ($item->team): ?>
							<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=team&id=' . $item->team) ?>"><?php echo $item->team_title; ?></a>
						<?php elseif ($item->department): ?>
							<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=department&id=' . $item->department) ?>"><?php echo $item->department_title; ?></a>
						<?php endif; ?>
					</p>
					<p><?php echo JHtml::_('string.truncate', strip_tags(trim($item->description)), 380); ?></p>
					<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=report&id=' . $item->id) ?>" class="btn">
						<span class="icon-chevron-right"></span><?php echo JText::_('COM_VOLUNTEERS_READ_MORE') ?>&nbsp;<?php echo JHtml::_('string.truncate', $item->title, 55); ?>
					</a>
				</div>
			</div>
			<hr>
		<?php endforeach; ?>
		<a class="btn btn-large btn-block" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=reports'); ?>"><?php echo JText::_('COM_VOLUNTEERS_READ_MORE_REPORTS') ?></a>
	</div>

	<div class="span4">
		<div class="well">
			<h2><?php echo JText::_('COM_VOLUNTEERS_JOOMLASTORY') ?></h2>
			<ul class="media-list">
				<li class="media">
					<a class="pull-left" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $this->volunteerstory->id) ?>">
						<?php echo VolunteersHelper::image($this->volunteerstory->image, 'small'); ?>
					</a>
					<div class="media-body">
						<h4 class="media-heading">
							<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $this->volunteerstory->id) ?>">
								<?php echo($this->volunteerstory->firstname . ' ' . $this->volunteerstory->lastname); ?>
							</a>
						</h4>
						<p class="muted">
							<span class="icon-location"></span> <?php echo VolunteersHelper::location($this->volunteerstory->city, $this->volunteerstory->country); ?>
						</p>
					</div>
				</li>
				<li class="media">
					<p><?php echo JHtml::_('string.truncate', strip_tags(trim($this->volunteerstory->joomlastory)), 500); ?></p>
					<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $this->volunteerstory->id) ?>#joomlastory" class="btn">
						<span class="icon-chevron-right"></span><?php echo JText::_('COM_VOLUNTEERS_READ_MORE_JOOMLASTORY') ?>
					</a>
				</li>
			</ul>
		</div>

		<h2><?php echo JText::_('COM_VOLUNTEERS_LATEST_VOLUNTEERS') ?></h2>
		<?php if (!empty($this->volunteers)) foreach ($this->volunteers as $i => $item): ?>
			<ul class="media-list">
				<li class="media">
					<a class="pull-left" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $item->id) ?>">
						<?php echo VolunteersHelper::image($item->image, 'small'); ?>
					</a>
					<div class="media-body">
						<h4 class="media-heading">
							<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $item->id) ?>">
								<?php echo($item->firstname . ' ' . $item->lastname); ?>
							</a>
						</h4>
						<p class="muted">
							<span class="icon-location"></span> <?php echo VolunteersHelper::location($item->city, $item->country); ?>
						</p>
					</div>
				</li>
			</ul>
		<?php endforeach; ?>
		<a class="btn btn-large btn-block" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteers'); ?>"><?php echo JText::_('COM_VOLUNTEERS_READ_MORE_VOLUNTEERS') ?></a>
	</div>
</div>

<br>

<div class="row-fluid">
	<div class="span12">
		<h2><?php echo count($this->markers) . ' ' . JText::_('COM_VOLUNTEERS_VOLUNTEERS_WORLD') ?></h2>
		<div id="map-canvas" style="height: 400px; width: 100%"></div>
	</div>
</div>

<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC04czYnPuPFkO6eDAKX-j_lfrpanAAo-U"></script>

<script>
	function initialise() {
		var mapOptions = {
				zoom: 2,
				zoomControl: true,
				zoomControlOptions: {
					style: google.maps.ZoomControlStyle.SMALL
				},
				center: {lat: 25, lng: 15},
				mapTypeId: google.maps.MapTypeId.ROADMAP,
				panControl: false,
				mapTypeControl: false,
				scaleControl: false,
				streetViewControl: false,
				overviewMapControl: false,
				rotateControl: false,
				draggable: !("ontouchend" in document)
			},
			mcOptions = {
				styles: [{
					height: 53,
					url: "media/com_volunteers/images/m1.png",
					width: 53
				},
					{
						height: 56,
						url: "media/com_volunteers/images/m2.png",
						width: 56
					},
					{
						height: 66,
						url: "media/com_volunteers/images/m3.png",
						width: 66
					},
					{
						height: 78,
						url: "media/com_volunteers/images/m4.png",
						width: 78
					},
					{
						height: 90,
						url: "media/com_volunteers/images/m5.png",
						width: 90
					}
				]
			}

		var map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

		map.addListener('click', function () {
			map.set('draggable', true);
		});

		var markers = [];
		var bounds = new google.maps.LatLngBounds();
		var infoWindow = new google.maps.InfoWindow();
		var locations = [ <?php echo implode(',', $this->markers) ?> ];

		var icon = {
			url: "media/com_volunteers/images/joomla.png", // url
			scaledSize: new google.maps.Size(50, 50), // scaled size
			origin: new google.maps.Point(0, 0), // origin
			anchor: new google.maps.Point(25, 25) // anchor
		};

		for (i = 0; i < locations.length; i++) {
			marker = new google.maps.Marker({
				position: new google.maps.LatLng(locations[i].lat, locations[i].lng),
				map: map,
				icon: icon
			});

			google.maps.event.addListener(marker, 'click', (function (marker, i, infoWindow) {
				return function () {
					infoWindow.setContent('<div style="width:200px;"><img width="40" class="pull-left" style="padding-right: 10px" src="' + locations[i].image + '" /><a href="' + locations[i].url + '">' + locations[i].title + '</a><br />' + locations[i].address + '</div>');
					infoWindow.open(map, marker);
				}
			})(marker, i, infoWindow));

			markers.push(marker);
			bounds.extend(marker.position);
		}

		var markerCluster = new MarkerClusterer(map, markers, mcOptions);

		var styles = [
			{
				featureType: "road",
				elementType: "geometry",
				stylers: [
					{lightness: 100},
					{visibility: "simplified"}
				]
			}, {
				featureType: "road",
				elementType: "labels",
				stylers: [
					{visibility: "simplified"}
				]
			}, {
				featureType: "poi",
				elementType: "labels",
				stylers: [
					{visibility: "on"}
				]
			}, {
				featureType: "poi.business",
				elementType: "labels",
				stylers: [
					{visibility: "off"}
				]
			}, {
				featureType: "water",
				elementType: "labels",
				stylers: [
					{visibility: "on"}
				]
			}
		];
		map.fitBounds(bounds);
		map.setOptions({styles: styles});
	}

	google.maps.event.addDomListener(window, 'load', initialise);
</script>