/*
 * Javascript Library for SmartBotanics
 * Minh Mai 2015
 */

/*
 * Plant Classes
 * 
 */
function plant(data){
		jQuery.extend(this,data);
		//Perform some data conversion
		this.isValveOn=parseInt(this.isValveOn);
		this.isManualLightOn=parseInt(this.isManualLightOn);
		this.autoLight=parseInt(this.autoLight);
		this.autoWater=parseInt(this.autoWater);
		/*
		* create a display for the plant
		*/
		this.create_status_display = function()
		{
			var html='<!-- plant '+ this.id+' -->';
			html+='<div id="'+this.id+'">\n' +
				'<div class="panel panel-primary col-sm-4" style=" width: 300px;">\n' +
	    		'<div class="panel-heading">\n' +
	    			'<button type="button" class="close" aria-label="Close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>'+
	    			'<h4><span id="name_text_'+this.id+'">'+this.name+'</span>\n' +
	                        '<small>\n' +
	                            '<span class="glyphicon glyphicon-cog"  aria-hidden="true" onclick="trigger_plant_config(' + this.id +',\'' + escape(this.name)+ '\');"></span>\n' +
	                        '</small>\n' +
	                '</h4>\n' +
	                '<h6>\n' +
	                    'Location: <span id="plant_location_text_'+this.id+'">'+this.location+'</span>\n' +
	                    '<span id ="health_status_'+ this.id + '" class="label label-success pull-right"></span>\n' +
	                '</h6>\n' +
	    		'</div>\n' +
	    		'<div class="panel-body">\n' +
	    			'<div class="center-block" name="instant_status">\n' +
	                    '<!-- Sensor Group -->\n' +
	                    '<div class="row">\n' +
	        				'<!--Moiture Level-->\n' +
	                        '<div class="col-sm-6 center-block">\n' +
	                            '<div class="circle" id="moisture_level_enclosure_'+this.id+'">\n' +
	                                '<strong id="moisture_level_text_'+this.id+'">'+this.moisture+' %</strong>\n' +
	                                '<span> Moisture </span>\n' +
	                            '</div>\n' +
	                        '</div>\n' +
	        				'<!--End Moiture -->\n' +
	
	        				'<!--Light Level -->\n' +
	                        '<div class="col-sm-6 center-block">\n' +
	            				'<div class="circle" id="light_level_enclosure_'+this.id+'">\n' +
	                                '<strong id="light_level_text_'+this.id+'">'+this.light+' %</strong>\n' +
	                                '<span> Light </span>\n' +
	                            '</div>\n' +
	                        '</div>\n' +
	        				'<!--End Light -->\n' +
	                        '<br/>\n' +
	                    '</div>\n'  +
	                    '<!-- End Sensor Group -->\n' +
	
	                    '<!-- Button Group -->\n' +
	                    '<div class="row">\n' +
	                        '<div class="col-sm-6">\n' +
	    				        '<button type="button" class="btn btn-default" style="width:100%" id="toggle_water_'+this.id+'" onclick="toggle_water(' + this.id +');" >Valve On</button>\n' +
	                        '</div>\n' +
	                        '<div class="col-sm-6">\n' +
	    				        '<button type="button" class="btn btn-default" style="width:100%;min-width:100px" id="toggle_light_'+this.id+'" onclick="toggle_light(' + this.id +');">Light On</button>\n' +
	                        '</div>\n' +
	                    '</div>\n' +
	                    '<!-- End Button Group -->\n' +
	    			'</div>\n' +
	    		'</div> <!-- end panel body -->\n' +
	    	'</div>\n' + 
	    	'<!-- End Tree Component -->\n'+
			'</div>\n'+
			'<!-- End plant '+this.id+' -->';
			$("#plant_list").append(html);
			if (this.isValveOn) $("#toggle_water_"+this.id).text("Water OFF");
			else $("#toggle_water_"+this.id).text("Water ON");
			if (this.isManualLightOn) $("#toggle_light_"+this.id).text("Light OFF");
			else $("#toggle_light_"+this.id).text("Light ON");
			//Draw graphs representing the moisture and light level
			$('#moisture_level_enclosure_'+this.id).circleProgress({
		        value: this.moisture/100,
		        fill: { gradient: ['#4ac5f8','#0681c4'], gradientAngle: -Math.PI/2 },
		        thickness: 8
		    });
			$('#light_level_enclosure_'+this.id).circleProgress({
		        value: this.light/100,
		        fill: { gradient: ['orange','red'], gradientAngle: -Math.PI/2 },
		        thickness: 8
		    });
			this.update_health_status(this.health_status);
		}
		this.create_detail_display=function()
		{
			var html='<!-- plant '+ this.id+' -->';
			html+='<div id="'+this.id+'">\n' +
				'<div class="panel panel-primary col-sm-12">\n' +
	    		'<div class="panel-heading">\n' +
	    			'<button type="button" class="close" aria-label="Close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>'+
	    			'<h4><span id="name_text_'+this.id+'">'+this.name+'</span>\n' +
	                        '<small>\n' +
	                            '<span class="glyphicon glyphicon-cog"  aria-hidden="true" onclick="trigger_plant_config(' + this.id +',\'' + escape(this.name)+ '\');"></span>\n' +
	                        '</small>\n' +
	                '</h4>\n' +
	                '<h6>\n' +
	                    'Location: <span id="plant_location_text_'+this.id+'">'+this.location+'</span>\n' +
	                    '<span id ="health_status_'+ this.id + '" class="label label-success pull-right"></span>\n' +
	                '</h6>\n' +
	    		'</div>\n' +
	    		'<div class="panel-body">\n' +
	    			'<div class="center-block col-sm-6" id="moisture_graph_'+this.id+'">\n' +
                
	    			'</div>\n' +
    			
	    			'<div class="center-block col-sm-6" id="light_graph_'+this.id+'">\n' +
	                 
	    			'</div>\n' +
	    			
	    		'</div> <!-- end panel body -->\n' +
	    	'</div>\n' + 
	    	'<!-- End Tree Component -->\n'+
			'</div>\n'+
			'<!-- End plant '+this.id+' -->';
			$("#plant_list").append(html);
			Highcharts.setOptions({
	            global: {
	                useUTC: false
	            }
	        });
			this.draw_light_graph();
			this.draw_moisture_graph();
			this.update_health_status(this.health_status);
		}
		/**
		* This method is used to draw the light_graph
		*/
		this.draw_light_graph = function()
		{
	        this.light_chart= new Highcharts.Chart({
	            chart: {
	            	renderTo: "light_graph_"+this.id,
	                type: 'spline',
	                animation: Highcharts.svg, // don't animate in old IE
	                marginRight: 10
	            },
	            colors:['red'],
	            credits: {
	                text: 'SmartBotanics',
	                href: '#'
	            },
	            title: {
	                text: 'Enviroment Light Level in Lux'
	            },
	            xAxis: {
	                type: 'datetime',
	                tickPixelInterval: 150
	            },
	            yAxis: {
	                title: {
	                    text: 'Lux'
	                },
	                max: 1000,
	                min:0,
	                plotLines: [{
	                    value: 0,
	                    width: 1,
	                    color: 'red'
	                }]
	            },
	            tooltip: {
	                formatter: function () {
	                    return '<b>' + this.series.name + '</b><br/>' +
	                        Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', this.x) + '<br/>' +
	                        Highcharts.numberFormat(this.y, 2);
	                }
	            },
	            legend: {
	                enabled: false
	            },
	            exporting: {
	                enabled: false
	            },
	            series: [{
	                name: 'Light Level',
	                data: create_zero_array()
	            }]
	        });
			
		}
		this.draw_moisture_graph = function()
		{
			this.moisture_chart= new Highcharts.Chart({
	            chart: {
	            	renderTo: "moisture_graph_"+this.id,
	                type: 'spline',
	                animation: Highcharts.svg, // don't animate in old IE
	                marginRight: 10
	            },
	            colors:['blue'],
	            credits: {
	                text: 'SmartBotanics',
	                href: '#'
	            },
	            title: {
	                text: 'Moisture Level'
	            },
	            xAxis: {
	                type: 'datetime',
	                tickPixelInterval: 150
	            },
	            yAxis: {
	                title: {
	                    text: '%'
	                },
	                max:100,
	                min:0,
	                plotLines: [{
	                    value: 0,
	                    width: 1,
	                    color: 'blue'
	                }]
	            },
	            tooltip: {
	                formatter: function () {
	                    return '<b>' + this.series.name + '</b><br/>' +
	                        Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', this.x) + '<br/>' +
	                        Highcharts.numberFormat(this.y, 2);
	                }
	            },
	            legend: {
	                enabled: false
	            },
	            exporting: {
	                enabled: false
	            },
	            series: [{
	                name: 'Moisture Value',
	                data: create_zero_array()
	            }]
	        });
			
		}
		function create_zero_array()
		{
			var array=[];
			var time = (new Date()).getTime();
			for (var i=0;i< 10; i++)
			{
				array.unshift({
					x: time - i*10*1000,
					y: 0
				});
			}
			return array;
		}
		/**
		* This method is used to update the moisture and light level of the plant
		*/
		this.update_moisture_light = function(moisture,light)
		{
			
			this.moisture=moisture;
			$('#moisture_level_text_'+this.id).text(this.moisture +" %");
			$('#moisture_level_enclosure_'+this.id).circleProgress({value:this.moisture/100});
		
			this.light=light;
			$('#light_level_text_'+this.id).text(this.light +" %");
			$('#light_level_enclosure_'+this.id).circleProgress({value:this.light/100});
			
		}
		
		/**
		* This method is used to update the name and location of the plant
		*/
		this.update_name_location = function(name,plant_location)
		{
			this.name=name;
			this.plant_location=plant_location;
			$('#name_text_'+this.id).text(name);
			$('#plant_location_text_'+this.id).text(plant_location);
		}
		
		/**
		* This method is used to update the health status of the plant
		* health_status = -1 is bad
		* health_status = 0  is warning
		* health_status > 0 is good
		*/
		this.update_health_status = function(health_status)
		{
			this.health_status = health_status;
			$("health_status_"+this.id).removeClass();
			
			if (health_status==-1)
			{
				$("#health_status_"+this.id).addClass('label label-danger pull-right')
				$('#health_status_'+this.id).text('Bad');
			}
			else if (health_status==0)
			{
				$("#health_status_"+this.id).addClass('label label-warning pull-right')
				$('#health_status_'+this.id).text('Warning');
			}
			else
			{
				$("#health_status_"+this.id).addClass('label label-success pull-right')
				$('#health_status_'+this.id).text('Good');
			}
		}
	}