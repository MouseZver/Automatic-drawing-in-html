<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>MouseZver</title>
		<script src = "//php/76528/jquery-3.1.0.js"></script>
		<style>
			body { background-color:#595959;margin: 0 auto; margin-top: 50px; width: 1000px; border: 1px solid red; }
			td { padding: 5px; background-color:blue; /* transition: all .3s linear; */}
			.err { background-color: red; /* transition: all .3s linear; */ }
		</style>
	</head>
	<body>
		
	</body>
	<script>
		var max_x = 73;
		var max_y = 54;
		var username = 'mousezver';
		var maps = [];
		var colors = [
			'#00f',
			'#7fff00',
			'#2e2e3e',
			'#fff',
			'#EBCA9B',
			'#613403',
			'#F7396C', // красный
			'#AD2547',
			'#D216F7',
			'#A106A1',
			'#0DDBFF',
			'#3E88F7',
			'#03FC52',
			'#09A840',
			'#FFF000',
			'#FF9900',
			'#FF5900',
			'#D60D0D'
		];
		
		
		// конструктор куба
		var x = 1;
		var y = 1;
		var tab = '<table>';
		
		while ( y <= max_y )
		{
			if ( x == 1 ) tab = tab + '<tr>';
			
			tab = tab + '<td class = "' + x + '_' + y + '" data-color = "0"></td>';
			
			//maps.push( x + '_' + y + '_1' );
			
			if ( x == max_x )
			{
				tab = tab + '</tr>';
				
				x = 1;
				y++;
			}
			else x++;
		}
		
		document.body.innerHTML = '<div align = "center">' + tab + '</table></div>';
		
		//console.log( maps );
		
		$(function()
		{
			$.post( "//php/76528/zver.php", { user: username }, function( data )
			{
				if ( typeof ( obj = JSON.parse( data ) ).err === 'undefined' )
				{
					if ( typeof obj.points.add !== 'undefined' )
					{
						$.each( obj.points.add, function ( index, value )
						{
							p = value.split( '_' );
							
							maps.push( p[0] + '_' + p[1] );
							
							$( '.' + p[0] + '_' + p[1] ).data( 'color', p[2] );
							
							$( '.' + p[0] + '_' + p[1] ).css( { "background-color": colors[p[2]] } );
						} )
						
						/* i = 0;
						time = 500;
						var speed = {
							'1_4': 200,
							'1_11': 100,
							'1_12': 25,
							'9_5': 12,
							'1_31': 5,
							'63_1': 3,
							'58_29': 1
						};
						
						( function()
						{
							if ( i < obj.points.add.length )
							{
								p = obj.points.add[i].split( '_' );
								
								//maps.push( p[0] + '_' + p[1] );
								
								//$( '.' + p[0] + '_' + p[1] ).data( 'color', p[2] );
								
								time = speed[p[0] + '_' + p[1]] ? speed[p[0] + '_' + p[1]] : time;
								
								n = 1;
								
								if ( time !== 1 )
								{
									( function()
									{
										if ( n <= p[2] )
										{
											$( '.' + p[0] + '_' + p[1] ).css( { "background-color": colors[n] } );
											
											n++;
										
											setTimeout(arguments.callee, time );
										}
										
										
									} )();
								}
								else
								{
									$( '.' + p[0] + '_' + p[1] ).css( { "background-color": colors[p[2]] } );
								}
								
								i++;
								
								setTimeout(arguments.callee, time*3*p[2] );
							}
						} )(); */
					}
				}
			})
			
			function setMaps( data )
			{
				if ( typeof ( obj = JSON.parse( data ) ).err === 'undefined' )
				{
					if ( typeof obj.points.add !== 'undefined' )
					{
						map = obj.points.add[0].split( '_' );
						
						if ( maps.indexOf( map[0] + '_' + map[1] ) === -1 )
						{
							maps.push( map[0] + '_' + map[1] );
						}
						
						$( '.' + map[0] + '_' + map[1] ).data( 'color', map[2] );
						
						$( '.' + map[0] + '_' + map[1] ).css( { "background-color": colors[map[2]] } );
					}
					
					if ( typeof obj.points.unset !== 'undefined' )
					{
						map = obj.points.unset[0].split( '_' );
						
						maps.splice( maps.indexOf( map[0] + '_' + map[1] ), 1 );
						
						$( '.' + map[0] + '_' + map[1] ).data( 'color', 0 );
						
						$( '.' + map[0] + '_' + map[1] ).css( { "background-color": colors[0] } );
					}
					
					if ( typeof obj.points.err !== 'undefined' )
					{
						map = obj.points.err[0].split( '_' );
						
						$( '.' + map[0] + '_' + map[1] ).addClass( 'err' );
						
						setTimeout( function()
						{
							$( '.' + map[0] + '_' + map[1] ).removeClass( 'err' );
						}, 500 );
					}
				}
			}
			
			$( 'body' ).on( 'mousedown', 'td', function ( event )
			{
				$( this ).contextmenu( false );
				
				a = $( this ).attr( 'class' ).split( '_' );
				
				if ( event.button == 2 )
				{
					color = $( this ).data( 'color' );
					
					$.post( "//php/76528/zver.php", { user: username, x: a[0], y: a[1], color: color ? color : 1 }, setMaps );
				}
				
				if ( event.button == 0 && maps.indexOf( $( this ).attr( 'class' ) ) !== -1 )
				{
					color = $( this ).data( 'color' );
					
					if ( ( colors.length - 1 ) == ( color++ ) )
					{
						color = 2;
					}
					
					console.log( $( this ).data( 'color' ), color, colors[color] );
					
					$.post( "//php/76528/zver.php", { user: username, x: a[0], y: a[1], color: color }, setMaps );
				}
			})
		})
	</script>
</html>