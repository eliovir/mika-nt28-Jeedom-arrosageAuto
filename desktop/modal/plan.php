<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$eqLogics = array();
foreach (eqLogic::byType('arrosageAuto') as $eqLogic){
	$info =array();
	$object = $eqLogic->getObject();
	if (is_null($object)) {
		$object = 'Aucun';
	} else {
		$object = $object->getName();
	}
	$info['name'] = $eqLogic->getName().' [' . $object . ']';
	$info['arroseur'] = $eqLogic->getConfiguration('arroseur');
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
	for (eqlogic in eqLogics) {
		graph.addNode(eqlogic,{url : 'plugins/arrosageAuto/3rdparty/Source.png',eqlogic :1,x:0,y:0});
		topin = graph.getNode(eqlogic);
		topin.isPinned = true;
		for (arroseur in eqLogics[eqlogic]['arroseur']) 
			graph.addNode("Arroseur - "+arroseur,{url : 'plugins/arrosageAuto/3rdparty/Arroseur.png',eqlogic :0,x:0,y:0});
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
                return Viva.Graph.svg('line').attr('stroke', color).attr('stroke-dasharray', dashvalue).attr('stroke-width', '0.6px');
        });
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
