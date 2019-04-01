<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$planPosition=config::byKey('planPosition', 'arrosageAuto');
$Position = array();
echo json_encode($planPosition);
$Position['Source']['x']=intval($planPosition['Source']['x']);
$Position['Source']['y']=intval($planPosition['Source']['y']);
foreach (eqLogic::byType('arrosageAuto') as $eqLogic){
	//if(isset($Position[$eqLogic->getName().' [' . $object . ']'])){
		$info =array();
		$object = $eqLogic->getObject();
		if (is_null($object)) {
			$object = 'Aucun';
		} else {
			$object = $object->getName();
		}
		$info['name'] = $eqLogic->getName().' [' . $object . ']';
        foreach($eqLogic->getConfiguration('arroseur') as $key => $Arroseur){
          $info['arroseur'][$info['name'] . ' - '.$key]=$Arroseur;
          $info['arroseur'][$info['name'] . ' - '.$key]['x']=intval($planPosition[$info['name'] . ' - '.$key]['x']);
          $info['arroseur'][$info['name'] . ' - '.$key]['y']=intval($planPosition[$info['name'] . ' - '.$key]['x']);
        }
		$info['x']=intval($planPosition[$info['name']]['x']);
		$info['y']=intval($planPosition[$info['name']]['y']);
		$Position[$eqLogic->getName().' [' . $object . ']']=$info;
	//}
}
//config::save('planPosition', $Position,'arrosageAuto');
sendVarToJS('eqLogics', $Position);
$background=array_diff(scandir('plugins/arrosageAuto/plan/'), array('..', '.'));
reset($background);
$background = 'plugins/arrosageAuto/plan/'.$background[key($background)];
?>
<script type="text/javascript" src="plugins/arrosageAuto/3rdparty/vivagraph/vivagraph.min.js"></script>
<script type="text/javascript" src="plugins/arrosageAuto/3rdparty/maphilight/jquery.maphilight.min.js"></script>
<style>
</style>
<div id="plan_arrosage" class="tab-pane" usemap="#map">
	<a class="btn btn-success arrosageAutoAction" data-action="savearroseur"><i class="fa fa-floppy-o"></i> {{Position arroseurs}}</a>
	<a class="btn btn-success arrosageAutoAction" data-action="refresh"><i class="fa fa-refresh"></i></a>
	<input type="file" name="PlanImg" id="PlanImg" data-url="plugins/arrosageAuto/core/ajax/arrosageAuto.ajax.php?action=PlanImg" placeholder="{{Image de fond}}" class="form-control input-md"/>
	<img class="CameraSnap"  src=""/>
	<div id="div_displayArea"></div>
	<map name="map" id="map"></map>
</div>
<style>
    #plan_arrosage {
	background-repeat: no-repeat;
	background-image:url(<?php echo $background; ?>);
  	position:relative;
        height: 100%;
        width: 100%;
        position: absolute;
    }
    #plan_arrosage > svg {
        height: 100%;
        width: 100%
    }
.cornerResizers {
  	display: block;
  	position: absolute;
  	width: 6px;
	height: 6px;
	background-color: #333;
	border: 1px solid #fff;
	overflow: hidden;
	cursor: move;
}
.medianResizers {
	display: block;
	position: absolute;
	width: 4px;
	height: 4px;
	background-color: #fff;
	border: 1px solid #333;
	overflow: hidden;
	cursor: move;
}
</style>
<script>
var coords=[];
/*if(areas.length>2){
	var coords=JSON.parse(areas);
}*/	
$('#PlanImg').fileupload({
	dataType: 'json',
	replaceFileInput: false,
	//done: function (data) {
	success: function(data) {
		if (data.state != 'ok') {
			$('#div_alert').showAlert({message: data.result, level: 'danger'});
			return;
		}
		$('#plan_arrosage').css("background-image", "url(plugins/arrosageAuto/plan/'+data.result)"); 
	}
});
/*$('body').on('click', '#plan_arrosage', function (e) {
	setCoordinates(e);
}); */
var onImgLoad = function(selector, callback){
    $(selector).each(function(){
        if (this.complete || /*for IE 10-*/ $(this).height() > 0) {
            callback.apply(this);
        }
        else {
            $(this).on('load', function(){
                callback.apply(this);
            });
        }
    });
};
function hightlight(){
	$('#plan_arrosage').maphilight({
		stroke: true,
		fade: true, 
		strokeColor: '4F95EA',
		alwaysOn: true,
		fillColor: '365E71',
		fillOpacity: 0.2,
		shadow: true,
		shadowColor: '000000',
		shadowRadius: 5,
		shadowOpacity: 0.6,
		shadowPosition: 'outside'
	});
};
function setCoordinates(e) {
	var x = e.pageX;
	var y = e.pageY;
	var offset = $('#plan_arrosage').offset();
	x -= parseInt(offset.left);
	y -= parseInt(offset.top);
	if(x < 0) { x = 0; }
	if(y < 0) { y = 0; }
    if(x!=null && y!=null){
        coords.push([x,y]);
        updateCoords();
    }
}
function updateCoords() {
  	areas=JSON.stringify(coords);
	var shape = (coords.length <= 2) ? 'rect' : 'poly';
	$('#map').html($('<area>')
		.addClass("area") 
		.attr('shape',shape)
		.attr('coords',coords.toString()));
	hightlight();
	editPolygon();
}
function editPolygon() {
	$('#div_displayArea').html('');
	for(var loop=0; loop< coords.length;loop++)
	{
		var coord=coords[loop];
		var coordX=parseInt(coord[0]);
		var coordY=parseInt(coord[1]);
		var cornerDiv=$('<div id="corner_' + loop + '" class="cornerResizers"></div>');
		var interDiv=$('<div id="inter_' + loop + '" class="medianResizers"></div>');
		
		// Add resizers
		$('#div_displayArea').append(cornerDiv);
		$('#div_displayArea').append(interDiv);
		
	  // Set and fix resizer dimensions if neeeded (only even values allowed)
		var cornerWidth = parseInt(cornerDiv.css('width').replace(/px$/,""));
		if (cornerWidth % 2 != 0) {
		  cornerWidth++;
		  cornerDiv.css('width', cornerWidth.toString() + 'px');
		}
		var cornerHeight = parseInt(cornerDiv.css('height').replace(/px$/,""));
		if (cornerHeight % 2 != 0) {
		  cornerHeight++;
		  cornerDiv.css('height', cornerHeight.toString() + 'px');
		}
		var interWidth = parseInt(interDiv.css('width').replace(/px$/,""));
		if (interWidth % 2 != 0) {
			interWidth++;
			interDiv.css('width', interWidth.toString() + 'px');
		}
		var interHeight = parseInt(interDiv.css('height').replace(/px$/,""));
		if (interHeight % 2 != 0) {
			interHeight++;
			interDiv.css('height', interHeight.toString() + 'px');
		}
		// Set corner resizer position
		cornerDiv.css('left', Math.round(coordX - (cornerWidth / 2) - 1) + 'px');
		cornerDiv.css('top', Math.round(coordY - (cornerHeight / 2) - 1) + 'px');
		// Set median resizer position
		if (loop == (coords.length - 1)) {
		interDiv.css('left', Math.round(((coordX + parseInt(coords[0][0])) / 2) - (interWidth / 2) - 1) + 'px');
		interDiv.css('top', Math.round(((coordY + parseInt(coords[0][1])) / 2) - (interHeight / 2) - 1) + 'px');
		} else {
		interDiv.css('left', Math.round(((coordX + parseInt(coords[loop+1][0])) / 2) - (interWidth / 2) - 1) + 'px');
		interDiv.css('top', Math.round(((coordY  + parseInt(coords[loop+1][1])) / 2) - (interHeight / 2) - 1) + 'px');
		}
		if (coords.length > 1) {
			// Setup dragging for corner resizer
			cornerDiv.draggable({
				scroll: false,
				opacity: 0.50,
				zIndex: 500,
				delay: 50,
				drag: function(e, ui) {
					// Get middle position of resizer
					var x = Math.round(ui.position.left) + (cornerWidth / 2) + 1;
					var y = Math.round(ui.position.top) + (cornerHeight / 2) + 1;
					coords[$(this).attr('id').split('_')[1]][0]=x;
					coords[$(this).attr('id').split('_')[1]][1]=y;
					updateCoords();
				},
			});
			// Catch right click on corner resizer and remove point
			cornerDiv.contextmenu(function(e) {
				coords.splice(parseInt($(this).attr('id').split('_')[1]),1);					
				updateCoords();
			});
			
			// Setup dragging for corner resizer
			interDiv.draggable({
				scroll: false,
				opacity: 0.50,
				zIndex: 500,
				delay: 50,
				stop: function(e, ui) {
					// Get middle position of resizer
					var x = Math.round(ui.position.left) + (cornerWidth / 2) + 1;
					var y = Math.round(ui.position.top) + (cornerHeight / 2) + 1;
					var coord=[x,y];
					coords.splice(parseInt($(this).attr('id').split('_')[1])+1,0,coord);					
					updateCoords();
				},
			});
		}
	}
}
load_graph();
function load_graph(){
    $('#plan_arrosage svg').remove();
	var graph = Viva.Graph.graph();
	graph.addNode('Source',{url : 'plugins/arrosageAuto/3rdparty/Source.png',x:eqLogics['Source']['x'],y:eqLogics['Source']['y']});	
	for (eqlogic in eqLogics) {
      alert(eqlogic+' - '+eqLogics[eqlogic]['x']);
		graph.addNode(eqlogic,{url : 'plugins/arrosageAuto/3rdparty/Source.png',x:eqLogics[eqlogic]['x'],y:eqLogics[eqlogic]['y']});
		graph.addLink(eqlogic,'Source');
		topin = graph.getNode(eqlogic);
		topin.isPinned = true;
		var lastArroseur = ''; 
		for (arroseur in eqLogics[eqlogic]['arroseur']) {
			graph.addNode(arroseur,{url : 'plugins/arrosageAuto/3rdparty/Arroseur.png',x:eqLogics[eqlogic]['arroseur'][arroseur]['x'],y:eqLogics[eqlogic]['arroseur'][arroseur]['y']});
			if(lastArroseur == '')
             			graph.addLink(arroseur,eqlogic);
			else
              			graph.addLink(arroseur,lastArroseur);
			lastArroseur = arroseur;
          	topin = graph.getNode(arroseur);
			topin.isPinned = true;
		}
	}
	var graphics = Viva.Graph.View.svgGraphics();
	highlightRelatedNodes = function (nodeId, isOn) {
		graph.forEachLinkedNode(nodeId, function (node, link) {
			var linkUI = graphics.getLinkUI(link.id);
			if (linkUI) {
				linkUI.attr('stroke-width', isOn ? '2.2px' : '0.6px');
			}
		});
	};
	graphics.node(function(node) {
		name = node.id;
		if (name == 'local'){
			name = 'Local';
		}
		var ui = Viva.Graph.svg('g'),
		    svgText = Viva.Graph.svg('text').attr('y', '-4px').text(name),
		    img = Viva.Graph.svg('image')
			.attr('width', 48)
			.attr('height', 48)
			.link(node.data.url);
		ui.append(svgText);
		ui.append(img);
		$(ui).hover(function () {
		    highlightRelatedNodes(node.id, true);
		}, function () {
		    highlightRelatedNodes(node.id, false);
		});
		return ui;	
    	})
    	.placeNode(function(nodeUI, pos){
		nodeUI.attr('transform',
			'translate(' +
				(pos.x - 24) + ',' + (pos.y - 24) +
			')');
    	});
	var idealLength =400;
	var layout = Viva.Graph.Layout.forceDirected(graph, {
                springLength: idealLength,
                stableThreshold: 0.1,
                dragCoeff: 0.02,
                springCoeff: 0.0005,
                gravity: -0.5
        });
	graphics.link(function (link) {
		dashvalue = '5, 0';
		color = 'green';
                return Viva.Graph.svg('line').attr('stroke', color).attr('stroke-dasharray', dashvalue).attr('stroke-width', '0.6px');
        });
	var renderer = Viva.Graph.View.renderer(graph, {
	        graphics : graphics,
		layout : layout,
		prerender : 10000,
		container: document.getElementById('plan_arrosage')
    	});
	renderer.run();
	$('.arrosageAutoAction[data-action=refresh]').on('click',function(){
		$('#md_modal').dialog('close');
			$('#md_modal').dialog({title: "{{Plan d'arrosage}}"});
			$('#md_modal').load('index.php?v=d&plugin=arrosageAuto&modal=plan&id=arrosageAuto').dialog('open');
	});
	$('.arrosageAutoAction[data-action=savearroseur]').on('click',function(){
      	var arroseur= {}
		graph.forEachNode(function (node) {
          var position = layout.getNodePosition(node.id);
          arroseur[node.id] = new Object();
          arroseur[node.id].x=position.x;
          arroseur[node.id].y=position.y;
		});
		jeedom.config.save({
          	plugin:'arrosageAuto',
            configuration: {'planPosition': arroseur},
            error: function (error) {
                $('#div_alert').showAlert({message: error.message, level: 'danger'});
            },
            success: function () {
                $('#div_alert').showAlert({message: '{{Sauvegarde réussie}}', level: 'success'});
            }
        });
	});
}
</script>
