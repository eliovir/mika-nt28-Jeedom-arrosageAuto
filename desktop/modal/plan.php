<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$eqLogics = array();
$infolocal=array();
$infolocal['x'] = config::byKey('positionx', 'arrosageAuto', 999);
$infolocal['y'] = config::byKey('positiony', 'arrosageAuto', 999);
$antennas['local']=$infolocal;
foreach (eqLogic::byType('arrosageAuto') as $eqLogic){
	$info =array();
	$object = $eqLogic->getObject();
	if (is_null($object)) {
		$object = 'Aucun';
	} else {
		$object = $object->getName();
	}
	$info['name'] = $eqLogic->getName().' [' . $object . ']';
	$info['icon'] = $eqLogic->getConfiguration('iconModel');
	$info['rssi'] = array();
	foreach ($eqLogic->getCmd('info') as $cmd) {
		$logicalId = $cmd->getLogicalId();
		if (substr($logicalId,0,4) == 'rssi'){
			$remotename= substr($logicalId,4);
			$remoterssi = $cmd->execCmd();
			$info['rssi'][$remotename] = $remoterssi;
		}
	}
	$eqLogics[$eqLogic->getName().' [' . $object . ']']=$info;
}
sendVarToJS('eqLogics', $eqLogics);
?>
<script type="text/javascript" src="plugins/arrosageAuto/3rdparty/vivagraph/vivagraph.min.js"></script>
<style>
    #graph_network {
        height: 100%;
        width: 100%;
        position: absolute;
    }
    #graph_network > svg {
        height: 100%;
        width: 100%
    }
</style>
<div id="graph_network" class="tab-pane">
	<a class="btn btn-success arrosageAutoRemoteAction" data-action="saveanttenna"><i class="fa fa-floppy-o"></i> {{Position Arroseurs}}</a>
	<a class="btn btn-success arrosageAutoRemoteAction" data-action="refresh"><i class="fa fa-refresh"></i></a>
</div>

<script>
load_graph();
function load_graph(){
    $('#graph_network svg').remove();
	var graph = Viva.Graph.graph();
	/*for (antenna in antennas) {
		if (antenna == 'local'){
			graph.addNode(antenna,{url : 'plugins/arrosageAuto/3rdparty/jeeblue.png',antenna :1,x:antennas[antenna]['x'],y:antennas[antenna]['y']});
		} else {
			graph.addNode(antenna,{url : 'plugins/arrosageAuto/3rdparty/antenna.png',antenna :1,x:antennas[antenna]['x'],y:antennas[antenna]['y']});
		}
		topin = graph.getNode(antenna);
		topin.isPinned = true;
	}*/
	for (eqlogic in eqLogics) {
		graph.addNode(eqlogic,{url : 'plugins/arrosageAuto/3rdparty/jeeblue.png',antenna :1,x:0,y:0});
		topin = graph.getNode(eqlogic);
		topin.isPinned = true;
		/*haslink = 0;
		graph.addNode(eqLogics[eqlogic]['name'],{url : 'plugins/arrosageAuto/core/config/devices/'+eqLogics[eqlogic]['icon']+'.jpg',antenna :0});
		for (linkedantenna in eqLogics[eqlogic]['rssi']){
			signal = eqLogics[eqlogic]['rssi'][linkedantenna];
			orisignal = signal;
			if (signal == -200 || signal == ''){
				quality = 200;
			} else if(signal <= -100){
				quality = 0;
			} else if(signal >= -50){
				quality = 100;
			}else{
				quality = 2 * (signal + 100);
			}
			lenghtfactor = quality/100;
			if (lenghtfactor != 2){
				haslink=1;
				graph.addLink(linkedantenna,eqLogics[eqlogic]['name'],{isdash: 0,lengthfactor: lenghtfactor,signal : orisignal});
			}
		}
		if (haslink != 0){
			for (antenna in antennas){
				linked = 0;
				for (linkedantenna in eqLogics[eqlogic]['rssi']){
					if (antenna == linkedantenna && eqLogics[eqlogic]['rssi'][linkedantenna] != -200){
						linked = 1;
					}
				}
				if (linked == 0){
					graph.addLink(antenna,eqLogics[eqlogic]['name'],{isdash: 1,lengthfactor: -0.1,signal : -200});
				}
			}
		}
		if (haslink == 0){
			graph.addLink('local',eqLogics[eqlogic]['name'],{isdash: 1,lengthfactor: 0.5,signal : -200});
		}*/
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
                gravity: -0.5,
                springTransform: function (link, spring) {
                    spring.length = idealLength * (1-link.data.lengthfactor);
                }
            });
		graphics.link(function (link) {
                dashvalue = '5, 0';
				color = 'green';
				if (link.data.signal <= -150) {
					color = 'grey';
				} else if (link.data.signal <= -92) {
					color = 'red';
				} else if (link.data.signal <= -86) {
					color = 'orange';
				} else if (link.data.signal <= -81) {
					color = 'yellow';
				}
                if (link.data.isdash == 1) {
                    dashvalue = '5, 2';
                }
                return Viva.Graph.svg('line').attr('stroke', color).attr('stroke-dasharray', dashvalue).attr('stroke-width', '0.6px');
            });
	/*for (antenna in antennas) {
		if (parseInt(antennas[antenna]['x']) != 999){
			layout.setNodePosition(antenna,parseInt(antennas[antenna]['x']),parseInt(antennas[antenna]['y']));
		}
	}*/
	var renderer = Viva.Graph.View.renderer(graph, {
	        graphics : graphics,
		layout : layout,
		prerender : 10000,
		container: document.getElementById('graph_network')
    	});
renderer.run();
$('.arrosageAutoRemoteAction[data-action=refresh]').on('click',function(){
	$('#md_modal').dialog('close');
		$('#md_modal').dialog({title: "{{Plan d'arrosage}}"});
		$('#md_modal').load('index.php?v=d&plugin=arrosageAuto&modal=plan&id=arrosageAuto').dialog('open');
});
$('.arrosageAutoRemoteAction[data-action=savearroseur]').on('click',function(){
	var arroseur= {}
	graph.forEachNode(function (node) {
	if (node.data.arroseur == 1){
		var position = layout.getNodePosition(node.id);
		arroseur[node.id] = position.x +'|'+position.y;
	}
});

$.ajax({
            type: "POST",
            url: "plugins/arrosageAuto/core/ajax/arrosageAuto.ajax.php", 
            data: {
                action: "saveArroseurPosition",
				arroseurs: json_encode(arroseur)
            },
            dataType: 'json',
            error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) { // si l'appel a bien fonctionné
        if (data.state != 'ok') {
            $('#div_alert').showAlert({message: data.result, level: 'danger'});
            return;
        }
		$('#div_alert').showAlert({message: 'Positions des arroseurs sauvé avec succes', level: 'success'});
		$('#md_modal').dialog('close');
		$('#md_modal').dialog({title: "{{Plan d'arrosage}}"});
		$('#md_modal').load('index.php?v=d&plugin=arrosageAuto&modal=plan&id=arrosageAuto').dialog('open');
        }
    });
});

}
</script>
