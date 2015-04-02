/*
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * @name GeolocationMarker for Google Maps v3
 * @version version 1.0
 * @author Chad Killingsworth [chadkillingsworth at missouristate.edu]
 * Copyright 2012 Missouri State University
 * @fileoverview
 * This library uses geolocation to add a marker and accuracy circle to a map.
 * The marker position is automatically updated as the user position changes.
 */

eval(function(p,a,c,k,e,r){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('9 6(J,F,E){t o={\'Z\':z,\'1h\':\'1g\',\'1M\':z,\'22\':q,\'1f\':{\'1n\':\'/1q/1E/1J/y/1Q/1T.1V\',\'1e\':m d.c.19(18,18),\'1v\':m d.c.19(17,17),\'1F\':m d.c.15(0,0),\'1N\':m d.c.15(8,8)},\'1R\':z,\'7\':m d.c.14(0,0),\'1b\':\'1c 1d\',\'R\':2};b(F){o=3.r(o,F)}t p={\'Z\':z,\'v\':0,\'1r\':\'1s\',\'1t\':.4,\'1x\':\'1z\',\'1B\':.4,\'1D\':1,\'R\':1};b(E){p=3.r(p,E)}3.j=m d.c.1G(o);3.l=m d.c.1L(p);3.f=h;3.7=h;3.g=h;3.n(\'H\',h);3.n(\'I\',({1Z:q,20:24}));3.l.x(\'g\',3.j);b(J){3.u(J)}}6.5=m d.c.A;6.5.n=9(B,X){b(/^(?:7|f)$/i.S(B)){1i\'\\\'\'+B+\'\\\' 1j a 1k-1l 1m.\';}D b(/g/i.S(B)){3.u((X))}D{d.c.A.5.n.1o(3,1p)}};6.5.j=h;6.5.l=h;6.5.11=9(){k 3.g};6.5.T=9(){k(3.U(\'I\'))};6.5.1u=9(V){3.n(\'I\',V)};6.5.1w=9(){k 3.7};6.5.W=9(){b(3.7){k 3.l.W()}D{k h}};6.5.1y=9(){k 3.f};6.5.O=9(){k(3.U(\'H\'))};6.5.1A=9(f){3.n(\'H\',f)};6.5.w=-1;6.5.u=9(g){3.g=g;3.1C(\'g\');b(g){3.Y()}D{3.j.P(\'7\');3.l.P(\'10\');3.l.P(\'v\');3.f=h;3.7=h;N.y.1H(3.w);3.w=-1;3.j.u(g)}};6.5.1I=9(o){3.j.12(3.r({},o))};6.5.1K=9(p){3.l.12(3.r({},p))};6.5.13=9(7){t M=m d.c.14(7.s.1O,7.s.1P),G=3.j.11()==h;b(G){b(3.O()!=h&&7.s.f>3.O()){k}3.j.u(3.g);3.j.x(\'7\',3);3.l.x(\'10\',3,\'7\');3.l.x(\'v\',3,\'f\')}b(3.f!=7.s.f){d.c.A.5.n.16(3,\'f\',7.s.f)}b(G||3.7==h||!3.7.1S(M)){d.c.A.5.n.16(3,\'7\',M)}};6.5.Y=9(){t Q=3;b(N.y){3.w=N.y.1U(9(7){Q.13(7)},9(e){d.c.1W.1X(Q,"1Y",e)},3.T())}};6.5.r=9(L,K){21(t C 23 K){b(6.1a[C]!==q){L[C]=K[C]}}k L};6.1a={\'g\':q,\'7\':q,\'v\':q};',62,129,'|||this||prototype|GeolocationMarker|position||function||if|maps|google||accuracy|map|null||marker_|return|circle_|new|set|markerOpts|circleOpts|true|copyOptions_|coords|var|setMap|radius|watchId_|bindTo|geolocation|false|MVCObject|key|opt|else|opt_circleOpts|opt_markerOpts|mapNotSet|minimum_accuracy|position_options|opt_map|source|target|newPosition|navigator|getMinimumAccuracy|unbind|self|zIndex|test|getPositionOptions|get|positionOpts|getBounds|value|watchPosition_|clickable|center|getMap|setOptions|updatePosition_|LatLng|Point|call||34|Size|DISALLOWED_OPTIONS|title|Current|location|size|icon|pointer|cursor|throw|is|read|only|property|url|apply|arguments|media|strokeColor|1bb6ff|strokeOpacity|setPositionOptions|scaledSize|getPosition|fillColor|getAccuracy|61a0bf|setMinimumAccuracy|fillOpacity|notify|strokeWeight|plugin_googlemap3|origin|Marker|clearWatch|setMarkerOptions|site|setCircleOptions|Circle|draggable|anchor|latitude|longitude|images|optimized|equals|gpsloc|watchPosition|png|event|trigger|geolocation_error|enableHighAccuracy|maximumAge|for|flat|in|1000'.split('|'),0,{}))