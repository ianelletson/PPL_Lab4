<?php
	$local_root = 'http://' . $_SERVER['SERVER_NAME'];	
	include($_SERVER['DOCUMENT_ROOT'].'/include/common.php');
	$selectedtab = 4;
	include($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
	
	include($_SERVER['DOCUMENT_ROOT'].'/database/grabdata.php');
?>
	<script src="http://d3js.org/d3.v3.min.js" charset="utf-8"></script>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
    <script>

/*
* Global Variables
*/
        var drawingWindow;
        var drawingWindowWidth = 500;
        var drawingWindowHeight = 500;
        var isQuickZoomedIn = false;
        var isColumnClicked = false;
        var points;

        /**
        * Initializer to create the drawingWindow
        */
        function drawingWindowInit() {

            // Strings that hold the details of the canvas and window
            drawingWindowDetails = "resizable=yes, width=%s1, height=%s2";
            canvasDetails = 
                "<html>" + 
                    "<head>" +
                        "<title>Drawing Window</title>" +
                    "</head>" +
                    "<body>" +
                        "<canvas id=\"theCanvas\" height=\"%s1\" width=\"%s2\"></canvas>" +
                        "<table width=\"100%\">" + 
                            "<tr>" +  
                                "<td width=\"50%\" id=\"Selected Column\">Selected Column: <br> No Column Selected</td>" + 
                                "<td width=\"50%\" id=\"Curser Column\">Curser Column: <br> %%%</td>" + 
                            "</tr>" +
                            "<tr>" +                            
                                "<td id= \"Buttons\">" +
                                    "<button id=\"inButton\" type=\"button\">+</button>" +
                                    "<button id=\"outButton\" type=\"button\">-</button>" +
                                    "<button id=\"downloadButton\" type=\"button\">Download</button>" +
                                "</td>" +
                            "</tr>" +
                        "</table>" +
                    "</body>" + 
                "</html>";

            // Replaces the value holder with the correct value
            drawingWindowDetails = drawingWindowDetails.replace("%s1", "" + drawingWindowWidth).replace("%s2", "" + drawingWindowHeight);
            canvasDetails = canvasDetails.replace("%s1", "" + (drawingWindowWidth * .95)).replace("%s2", "" + (drawingWindowHeight * .95));

            // Creates the window with the appropriate details
            drawingWindow  = window.open("","_blank", drawingWindowDetails);
            drawingWindow.document.write(canvasDetails);
        }

/*
* Mouse Response Helper Methods
*/
        /**
        * A function that gets the current hovering position of the mouse
        * @param drawing canvas for columns, the event about the location of the mouse
        * @return the xy coordinates of the mouse position
        */
        function getMousePos(canvas, evt) {
            var rect = canvas.getBoundingClientRect();
            return {
              x: evt.clientX - rect.left,
              y: evt.clientY - rect.top
            };
        }

        /**
        * Calculates the distance between the mouse position and a column point.
        * @param the position of the mouse, the xy point of one column
        * @return boolean value if the mouse is near a column
        */
        function distanceCheck(mousePos,colPos){
            var canvas  = drawingWindow.document.getElementById("theCanvas");

            // Scaled points of the column based on the size of the canvas
            scaledX = (colPos[0] * canvas.width);
            scaledY = (canvas.height - colPos[1] * canvas.height) - 25;
            // distance between the mouse and the column
            var distance= Math.sqrt(Math.pow(mousePos.x-scaledX,2) + Math.pow(mousePos.y-scaledY,2));

            return distance;
        }

        /**
         * Test the mouse position against the rest of the columns. 
         * @param the position of the mouse on the canvas
         * @return the name of the column the mouse is over
         **/
        function testMouseToCol(mousePos, nameArray) {
            var colPos=[];
            var colosestColIndex = -1;
            var retName = "No Column";
            for (var i=0; i<points.length; i++) {
                colPos=[points[i][0],points[i][1]];
                if(distanceCheck(mousePos,colPos) <= 5) { // finds the first column near the mouse position
                    if(colosestColIndex === -1 || colosestColIndex > distanceCheck(mousePos, colPos)) {
                        colosestColIndex = i;
                        retName = nameArray[i];
                    }
                }
            }
            return retName;
        }

/*
* Display helper methods
*/
        /**
        * Rewrites the UI-display to have the name of the nearest column to the mouse position
        * @param the name of the coulmn nearest to the mouse position
        */
        function reWrite(message, id) {
            var canvas = drawingWindow.document.getElementById("theCanvas");
            var context = canvas.getContext("2d");

//            context.font = "bold %%px Arial".replace("%%", "" + Math.floor(16 * (canvas.height / 500)));

            drawingWindow.document.getElementById(id).innerHTML=id + ": <br>" + message;
        }

        /**
        * Resizes the canvas when the size of the drawingWindow changes
        */
        function resizeCanvas() {
            var canvas  = drawingWindow.document.getElementById("theCanvas");
            var maxLength = Math.max(drawingWindow.innerWidth, drawingWindow.innerHeight);

            canvas.width = maxLength * .95;
            canvas.height = maxLength * .95;
            var context = canvas.getContext("2d");

            isQuickZoomedIn = false;
            clickFunction();
        }

        /**
        * A double click method to zoom in on a certain position
        */
        function quickZoom() {
            var canvas  = drawingWindow.document.getElementById("theCanvas");
            if(!isQuickZoomedIn) {
                zoomIn(); 
                isQuickZoomedIn = true;

                // Auto scrolls the drawingWindow to center about the zoom location
                drawingWindow.scrollBy(canvas.width/4, canvas.height/4);
            }
            else {  
                zoomOut();
                isQuickZoomedIn = false;
            }
        }

        /**
        * Zooms in on a scale of 2
        */
        function zoomIn() {
            var canvas  = drawingWindow.document.getElementById("theCanvas");
            canvas.width = canvas.width * 2;
            canvas.height = canvas.height * 2;
            clickFunction();
        }

        /**
        * Zooms out on a scale of .5
        */
        function zoomOut() {
            var canvas  = drawingWindow.document.getElementById("theCanvas");
            canvas.width = canvas.width / 2;
            canvas.height = canvas.height / 2;
            clickFunction();
        }

/*
* Drawing Methods
*/
        /**
        * Helper function to draw the Comparison Triangle
        * @param The drawing canvas, the "pen" for drawing, and space for drawing
        */
        function drawComparisonTriangle(canvas, context, drawingGap) {
            triangleHeight = Math.sqrt(canvas.height * canvas.height - (canvas.width / 2) * (canvas.width / 2));

            // Triangle Section
            context.beginPath();
            context.moveTo(0,canvas.height-drawingGap);
            context.lineTo(canvas.width,canvas.height-drawingGap);
            context.lineTo(canvas.width / 2,(canvas.height - triangleHeight)-drawingGap);
            context.lineTo(0,canvas.height-drawingGap);
            context.closePath();
            context.strokeStyle="#000000";
            context.stroke();

            // Labels
            context.font = "bold 32px Arial";
            context.fillText("S", (canvas.width / 8), canvas.height / 2);
            context.fillText("C", canvas.width - (canvas.width / 8), canvas.height / 2);
            context.textAlign="center";
            context.fillText("B", canvas.width / 2, canvas.height);
        }

        /**
         * Plots each column as a point in the triangle
         * @param points of the columns being plotted, point that will be
         * highlighted, the drawing canvas, the "pen" for drawing, and size
         * of the points being plotted
         */
        function plotColumns(columns, columnOfIntrest, canvas, context, radius) {
            //For loop for each column
            var colInt;
            for (var i = 0; i < columns.length; i++) {
                // Checks if the column is the column of interest
                if (columnOfIntrest[0] == columns[i][0] &&
                    columnOfIntrest[1] == columns[i][1]) {
                    colInt = [columns[i][0], columns[i][1]];
                }
                else {
                    context.strokeStyle = "#3D352A";
                }

                // Drawing the point
                context.beginPath();
                context.arc(columns[i][0] * canvas.width, (canvas.height - columns[i][1] * canvas.height) - radius * radius, radius, 0, 2 * Math.PI, false);
                context.closePath();
                context.stroke();
            }
                // Drawing the point
                context.strokeStyle = "#FF0000";
                context.beginPath();
                context.arc(colInt[0] * canvas.width, (canvas.height - colInt[1] * canvas.height) - radius * radius, radius, 0, 2 * Math.PI, false);
                context.closePath();
                context.stroke();
        }

/*
* Column Math Methods
*/
        /**
         * Normalizes columns
         * @params : Array to normalize, Array of normalizers
         * returns : Array of normalized values
         */
        var normalizeCols = function (xArray, hArray) {
            var tempArray = [];
            for (var i = 0; i < xArray.length; i++) {
                var xNorm = xArray[i] / hArray[i];
                tempArray.push(xNorm);
            }
            return tempArray;
        }

        /**
         * Finds range of an array
         * @params : Array to find range, bool (false for min, true for max)
         * returns : A max or min determined by opt
         * yes I know this is janky and ugly. Could be better with enums
         */
        var rangeFinder = function (xArray, opt) {
            var retVal = 0;
            for (var i = 0; i < xArray.length; i++) {
                if (!opt) { // min calc
                    if (xArray[i] < retVal) {
                        retVal = xArray[i];
                    }
                }
                else { // max calc
                    if (xArray[i] > retVal) {
                        retVal = xArray[i];
                    }
                }
            }
            return retVal;
        }

        /**
         * Weights a variable by its range
         * @params : min and maxes of that var
         * returns : a weight for the variable
         */
        var weight = function (min, max) {
            var weight = 1 / (max - min);
            return weight;
        }

        /**
         * Normalizes X to N (weight)
         * @params : weight of var and weight of normalizer
         * returns : normalized weight of var X
         */
        var normWeight = function (xWeight, nWeight) {
            var normalized = (xWeight / nWeight) * 100;
            return normalized;
        }

        /**
         * Scales the given var x
         * @params : normalized array of x, min of x, normalized weight of x
         * returns : an array of scaled x
         */
        var scaleCols = function (xArray, xMin, xNormWeight) {
            var tempArray = [];
            for (var i = 0; i < xArray.length; i++) {
                var scale = (xArray[i] - xMin) * xNormWeight;
                tempArray.push(scale);
            }
            return tempArray;
        }

        /**
         * Normalizes scaled vars
         * @params : array of scaled values and two other vars to scale by
         * returns : an array of normalized, scaled vars
         */
        var scaleNormalizer = function (xArray, nArray1, nArray2) {
            var tempArray = [];
            for (var i = 0; i < xArray.length; i++) {
                var scaled = xArray[i] / (xArray[i] + nArray1[i] + nArray2[
                    i]);
                tempArray.push(scaled);
            }
            return tempArray;
        }

        /**
         * Transforms scaled, normalized points to Y coordinates
         * @params : array of scaled normalized points
         * returns : array of scaled Y coordinates
         */
        var transformY = function (xArray) {
            var tempArray = [];
            for (var i = 0; i < xArray.length; i++) {
                var y = xArray[i] * Math.sin(Math.PI / 3);
                tempArray.push(y);
            }
            return tempArray;
        }

        /**
         * Transforms scaled, normalized points to X coordinates
         * @params : array of scaled, normalized points, array of Y points
         * returns : array of scaled X coordinates
         */
        var transformX = function (xArray, yArray) {
            var tempArray = [];
            for (var i = 0; i < xArray.length; i++) {
                var x = xArray[i] + (yArray[i] / (Math.tan(Math.PI / 3)));
                tempArray.push(x);
            }
            return tempArray;
        }

        /**
         * Scales points using Dustin's scale
         * @params array of x, y points, scale, drawing gap (what are those?)
         * returns array of [x,y]
         */
        var makePoints = function (xArray, yArray) {
            var tempArray = [];
            for (var i = 0; i < xArray.length; i++) {
                var x = xArray[i];
                var y = yArray[i];

                tempArray[i] = [x, y];
            }
            return tempArray;
        }

        /**
         * Action Listener to draw the triangle and plot the points
         */
        function clickFunction() {
            // Checks to see if the drawingWindow needs to be initialized
            // or if it needs to re-opened
            if (!(drawingWindow) || drawingWindow.closed) {
                drawingWindowInit();
            }
            if (!drawingWindow.closed) {
                window.blur();
                drawingWindow.focus();
            }

            /*
             * This anonymous function gets the data currently stored locally
             * TODO: when we move this to production we must adjust location
             * To ensure that this runs, start a python web server from the
             * directory - python -m SimpleHTTPServer
             * we must call all functions from within here
             * this is because getting is asynchronous
             */
            var xy = [];
            var dataset;
            // This below will make an array based off of the headers we
            // want from the csv file.
            d3.csv("Http://hplccolumns.org/database.csv", function (data) {
                dataset = data.map(function (d) {
                    return [+d["id"], d["name"], +d["H"], +d["S"], +d[
                            "B"], +d["C28"]
                    ];
                });

                /*
                 * @param : the index in dataset from which to draw data
                 * return : an array of one kind of data
                 */
                var makeArray = function (k) {
                    tempArray = [];
                    for (var i = 0; i < dataset.length; i++) {
                        if (k === 1) {
                            tempArray.push(dataset[i][k]);
                        } else {
                        tempArray.push(parseFloat(dataset[i][k]));
                    }
                    }
                    return tempArray;S
                }
                var hArray = makeArray(2);
                var sArray = makeArray(3);
                var bArray = makeArray(4);
                var cArray = makeArray(5);
                var nameArray = makeArray(1);

                var canvas = drawingWindow.document.getElementById(
                    "theCanvas");
                var context = canvas.getContext("2d");
                var radius = 5;

                /**
                 * Does all the work required other than making initial arrays
                 * @params : data for each variable from the csv
                 * returns : 2D array of x and y values
                 */
                // TODO this still uses non-param vars for scaling purposes
                var getData = function(hArray, sArray, bArray, cArray) {
                    var sNorm = normalizeCols(sArray, hArray);
                    var bNorm = normalizeCols(bArray, hArray);
                    var cNorm = normalizeCols(cArray, hArray);

                    // false for min ; true for max
                    var min = false;
                    var max = true;
                    var sHMin = rangeFinder(sNorm, min);
                    var sHMax = rangeFinder(sNorm, max);
                    var bHMin = rangeFinder(bNorm, min);
                    var bHMax = rangeFinder(bNorm, max);
                    var cHMin = rangeFinder(cNorm, min);
                    var cHMax = rangeFinder(cNorm, max);

                    var sWeight = weight(sHMin, sHMax);
                    var bWeight = weight(bHMin, bHMax);
                    var cWeight = weight(cHMin, cHMax);

                    var sNormWeight = normWeight(sWeight, sWeight);
                    var bNormWeight = normWeight(bWeight, sWeight);
                    var cNormWeight = normWeight(cWeight, sWeight);

                    var sScaled = scaleCols(sNorm, sHMin, sNormWeight);
                    var bScaled = scaleCols(bNorm, bHMin, bNormWeight);
                    var cScaled = scaleCols(cNorm, cHMin, cNormWeight);

                    var sScaledNorm = scaleNormalizer(sScaled, bScaled, cScaled);
                    var bScaledNorm = scaleNormalizer(bScaled, sScaled, cScaled);
                    var cScaledNorm = scaleNormalizer(cScaled, sScaled, bScaled);

                    var yPoints = transformY(cScaledNorm);
                    var xPoints = transformX(bScaledNorm, yPoints);
                    var retPoints = makePoints(xPoints, yPoints);
                    return retPoints;
                }

                xy = getData(hArray, sArray, bArray, cArray);
                points = xy;

                // Clears canvas
                context.clearRect(0, 0, canvas.width, canvas.height);

                // Draws comparison triangle
                drawComparisonTriangle(canvas, context, radius * radius);

                columnPointsToBePlotted = xy;
                // columnPoints = xy;

                // Column of interest WILL GET SOME HOW
                var chosenColumn = columnPointsToBePlotted[0]; // TEST;

                // Plots the columns
                plotColumns(xy, chosenColumn, canvas, context, radius);

                var canvas= drawingWindow.document.getElementById('theCanvas');

                // Action Listeners
                drawingWindow.addEventListener('resize', resizeCanvas, false);
                drawingWindow.addEventListener('click', function(evt) {
                    var mousePos = getMousePos(canvas, evt);
                    reWrite(testMouseToCol(mousePos, nameArray), "Selected Column");
                }, false);

                drawingWindow.addEventListener('dblclick', quickZoom, false);
                drawingWindow.document.getElementById('inButton').onclick = zoomIn;
                drawingWindow.document.getElementById('outButton').onclick = zoomOut;
                drawingWindow.document.getElementById('downloadButton').onclick = function() {
                    //drawingWindow.open(canvas.toDataURL(),"_blank","resizable=yes, width=100, height=100");

                    pictureWindowDetails = "resizable=yes, width=%s1, height=%s2";

                    // Replaces the value holder with the correct value
                    pictureWindowDetails = pictureWindowDetails.replace("%s1", "" + drawingWindow.innerWidth).replace("%s2", "" + drawingWindow.innerHeight);

                    // Creates the window with the appropriate details
                    pictureDrawingWindow  = drawingWindow.open(canvas.toDataURL(), "_blank",pictureWindowDetails);
                }
                
                // adds listener for Mouse Move
                canvas.addEventListener('mousemove', function(evt) {
                    //gets mouse pos and creates the message the will be sent to the window. 
                    var mousePos = getMousePos(canvas, evt);
                    reWrite(testMouseToCol(mousePos, nameArray), "Curser Column");                    
                }, false);
                });
        }
    </script>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr align="center">
            <td>
                <table width="100%" border="0" cellpadding="0" cellspacing="0" style="padding-left:10px; padding-right:10px;">
                    <tr>
                        <td class="heading1" colspan="2">
                            Step #1: Select a Column to Compare
                        </td>
                    </tr>
                    <tr>
                    	<td class="text_main" colspan="2">
                        	<p style="margin-top: 20px; margin-bottom: 0px;">
                            <b>Select a column</b> to compare from the list below. A similarity factor, <i>F<sub>s</sub></i>, will be calculated for each of the other columns in the database (below).
                            </p>
                            <br/>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        	<select size=10 id="list_allcolumns" style="width:400px;" onchange="var selectedColID = document.getElementById('list_allcolumns').options[document.getElementById('list_allcolumns').selectedIndex].value;	sorter.calcNewFs(1, selectedColID, 0, 5, 6, 7, 8, 9);">
                            	<?php
									$columns = array();
									$x = 0;
									for ($i = 0; $i <= count($column_data); $i++)
									{
										if ($column_data[$i]["manufacturer"] != '')
										{
											$columns[$x]["id"] = $column_data[$i]["id"];
											$columns[$x]["name"] = $column_data[$i]["manufacturer"].' '.$column_data[$i]["name"];
											$x++;
										}
									}
									
									// Obtain a list of columns
									$manufacturer = array();
									$name = array();
									$id = array();
									
									foreach ($columns as $key => $row) 
									{
										$id[$key] = $row['id'];
										$manufacturer[$key] = $row['manufacturer'];
										$name[$key] = $row['name'];
									}
									
									array_multisort($manufacturer, SORT_ASC, SORT_STRING, $name, SORT_ASC, SORT_STRING, $columns);
									
									// Set first element as selected by default
									echo '<option value="'.$columns[0]["id"].'" selected="selected">'.$columns[0]["manufacturer"].' '.$columns[0]["name"].'</option>';
									
									for ($i = 1; $i <= count($columns); $i++)
									{
										echo '<option value="'.$columns[$i]["id"].'">'.$columns[$i]["manufacturer"].' '.$columns[$i]["name"].'</option>';
									}
								?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                    	<td>
                    		<button style="width:400px;" type="button" onclick="clickFunction()">Visualize Columns</button>
                    	</td>
                    </tr>
                </table>
                <br />
                <br />
                <table width="100%" border="0" cellpadding="0" cellspacing="0" style="padding-left:10px; padding-right:10px;">
                    <tr>
                        <td class="heading1" colspan="2">
                            Step #2: Compare to Other Columns
                        </td>
                    </tr>
                    <tr>
                    	<td class="text_main" colspan="2">
                        	<p style="margin-top: 20px; margin-bottom: 0px;">
                            	The <i>F<sub>s</sub></i> factor describes the similarity of two columns. A small <i>F<sub>s</sub></i> indicates that two columns are very similar. A large <i>F<sub>s</sub></i> factor indicates that two columns are very different. <b>To find the columns that are most similar to the one selected above, sort the column database below by <i>F<sub>s</sub></i> so that the smallest <i>F<sub>s</sub></i> values are listed first.</b> To find the columns that are most different from the one selected above, sort the column database below by <i>F<sub>s</sub></i> so that the largest <i>F<sub>s</sub></i> values are listed first. 
                            </p>
                        </td>
                    </tr>
                    <tr>
                    	<td class="text_main" colspan="2">
                        	<p style="margin-top: 20px; margin-bottom: 0px;">
                            Select filters to narrow down the list of stationary phases. <br /><b>To select multiple values</b> within a box, hold down 'Ctrl' while selecting values within the box. <br /><b>To select a range of values</b> within a box, select the starting value in the box, then hold down 'Shift' while selecting the ending value in the box.
                            </p>
                            <!--<p style="margin-top: 10px;">
                            You can also download the database:
                            </p>
                            <p><a href="<?php echo $local_root;?>/database/database.csv"><img src="<?php echo $local_root;?>/images/downloadarrow.png" style="vertical-align: middle; margin-right: 5px; margin-left: 5px;" border="0px"/>database.csv</a>
                            </p>-->
                            <br/>
                        </td>
                    </tr>
                </table>
                <table width="100%" border="0" cellpadding="0" cellspacing="2" class="filter">
                	<thead>
                	<tr>
                    	<th>
                        	Manufacturer
                        </th>
                        <th>
                        	Silica type
                        </th>
                        <th>
                        	<span style="font-family:'Times New Roman', Times, serif">H</span>
                        </th>
                        <th>
                        	<span style="font-family:'Times New Roman', Times, serif">S*</span>
                        </th>
                        <th>
                        	<span style="font-family:'Times New Roman', Times, serif">A</span>
                        </th>
                        <th>
                        	<span style="font-family:'Times New Roman', Times, serif">B</span>
                        </th>
                        <th>
                        	<span style="font-family:'Times New Roman', Times, serif">C</span> (pH 2.8)
                        </th>
                        <th>
                        	<span style="font-family:'Times New Roman', Times, serif">C</span> (pH 7.0)
                        </th>
                        <th>
                        	EB retention factor
                        </th>
                        <th>
                        	USP type
                        </th>
                        <th>
                        	Phase type
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                    	<td width="204px">
                        	<select multiple size=10 id="list_manufacturer" style="width:200px;">
                            	<?php
									foreach ($manufacturer_list as $this_manufacturer)
									{
										echo '<option>'.$this_manufacturer.'</option>';
									}
								?>
                            </select>
                            <script type="text/javascript">
								var selectmenu1 = document.getElementById("list_manufacturer")
								selectmenu1.onchange=function()
								{
									var filterarray = [];
									for (var i = 0; i < selectmenu1.options.length; i++)
									{
										if (selectmenu1.options[i].selected)
											filterarray.push(selectmenu1.options[i].text);
									}
								 	sorter.setfilter(3, filterarray, 0);
								}
							</script>
                            <br/>
                            <a href="javascript:void(0)" onclick="document.getElementById('list_manufacturer').selectedIndex = -1; sorter.setfilter(3, []);">remove filter</a>
                        </td>
                    	<td width="84px">
                        	<select multiple size=10 id="list_type" style="width:80px;">
                            	<?php
									foreach ($type_list as $this_type)
									{
										echo '<option>'.$this_type.'</option>';
									}
								?>
                            </select>
                            <script type="text/javascript">
								var selectmenu2 = document.getElementById("list_type")
								selectmenu2.onchange=function()
								{
									var filterarray = [];
									for (var i = 0; i < selectmenu2.options.length; i++)
									{
										if (selectmenu2.options[i].selected)
											filterarray.push(selectmenu2.options[i].text);
									}
								 	sorter.setfilter(4, filterarray, 0);
								}
							</script>
                            <br/>
                            <a href="javascript:void(0)" onclick="document.getElementById('list_type').selectedIndex = -1; sorter.setfilter(4, []);">remove filter</a>
                        </td>
                        <td width="104px">
                        	<select multiple size=10 id="list_H" style="width:100px;">
                            	<?php
									for ($i = 0; $i <= count($H_list_string); $i++)
									{
										echo '<option value="'.$H_list_num[$i].'">'.$H_list_string[$i].'</option>';
									}
								?>
                            </select>
                            <script type="text/javascript">
								var selectmenu3 = document.getElementById("list_H")
								selectmenu3.onchange=function()
								{
									var filterarray = [];
									for (var i = 0; i < selectmenu3.options.length; i++)
									{
										if (selectmenu3.options[i].selected)
											filterarray.push(selectmenu3.options[i].value);
									}
								 	sorter.setfilter(5, filterarray, 1);
								}
							</script>
                            <br/>
                            <a href="javascript:void(0)" onclick="document.getElementById('list_H').selectedIndex = -1; sorter.setfilter(5, []);">remove filter</a>
                        </td>
                        <td width="104px">
                        	<select multiple size=10 id="list_S" style="width:100px;">
                            	<?php
									for ($i = 0; $i <= count($S_list_string); $i++)
									{
										echo '<option value="'.$S_list_num[$i].'">'.$S_list_string[$i].'</option>';
									}
								?>
                            </select>
                            <script type="text/javascript">
								var selectmenu4 = document.getElementById("list_S")
								selectmenu4.onchange=function()
								{
									var filterarray = [];
									for (var i = 0; i < selectmenu4.options.length; i++)
									{
										if (selectmenu4.options[i].selected)
											filterarray.push(selectmenu4.options[i].value);
									}
								 	sorter.setfilter(6, filterarray, 1);
								}
							</script>
                            <br/>
                            <a href="javascript:void(0)" onclick="document.getElementById('list_S').selectedIndex = -1; sorter.setfilter(6, []);">remove filter</a>
                        </td>
                        <td width="104px">
                        	<select multiple size=10 id="list_A" style="width:100px;">
                            	<?php
									for ($i = 0; $i <= count($A_list_string); $i++)
									{
										echo '<option value="'.$A_list_num[$i].'">'.$A_list_string[$i].'</option>';
									}
								?>
                            </select>
                            <script type="text/javascript">
								var selectmenu5 = document.getElementById("list_A")
								selectmenu5.onchange=function()
								{
									var filterarray = [];
									for (var i = 0; i < selectmenu5.options.length; i++)
									{
										if (selectmenu5.options[i].selected)
											filterarray.push(selectmenu5.options[i].value);
									}
								 	sorter.setfilter(7, filterarray, 1);
								}
							</script>
                            <br/>
                            <a href="javascript:void(0)" onclick="document.getElementById('list_A').selectedIndex = -1; sorter.setfilter(7, []);">remove filter</a>
                        </td>
                        <td width="104px">
                        	<select multiple size=10 id="list_B" style="width:100px;">
                            	<?php
									for ($i = 0; $i <= count($B_list_string); $i++)
									{
										echo '<option value="'.$B_list_num[$i].'">'.$B_list_string[$i].'</option>';
									}
								?>
                            </select>
                            <script type="text/javascript">
								var selectmenu6 = document.getElementById("list_B")
								selectmenu6.onchange=function()
								{
									var filterarray = [];
									for (var i = 0; i < selectmenu6.options.length; i++)
									{
										if (selectmenu6.options[i].selected)
											filterarray.push(selectmenu6.options[i].value);
									}
								 	sorter.setfilter(8, filterarray, 1);
								}
							</script>
                            <br/>
                            <a href="javascript:void(0)" onclick="document.getElementById('list_B').selectedIndex = -1; sorter.setfilter(8, []);">remove filter</a>
                        </td>
                        <td width="104px">
                        	<select multiple size=10 id="list_C28" style="width:100px;">
                            	<?php
									for ($i = 0; $i <= count($C28_list_string); $i++)
									{
										echo '<option value="'.$C28_list_num[$i].'">'.$C28_list_string[$i].'</option>';
									}
								?>
                            </select>
                            <script type="text/javascript">
								var selectmenu7 = document.getElementById("list_C28")
								selectmenu7.onchange=function()
								{
									var filterarray = [];
									for (var i = 0; i < selectmenu7.options.length; i++)
									{
										if (selectmenu7.options[i].selected)
											filterarray.push(selectmenu7.options[i].value);
									}
								 	sorter.setfilter(9, filterarray, 1);
								}
							</script>
                            <br/>
                            <a href="javascript:void(0)" onclick="document.getElementById('list_C28').selectedIndex = -1; sorter.setfilter(9, []);">remove filter</a>
                        </td>
                        <td width="104px">
                        	<select multiple size=10 id="list_C70" style="width:100px;">
                            	<?php
									for ($i = 0; $i <= count($C70_list_string); $i++)
									{
										echo '<option value="'.$C70_list_num[$i].'">'.$C70_list_string[$i].'</option>';
									}
								?>
                            </select>
                            <script type="text/javascript">
								var selectmenu8 = document.getElementById("list_C70")
								selectmenu8.onchange=function()
								{
									var filterarray = [];
									for (var i = 0; i < selectmenu8.options.length; i++)
									{
										if (selectmenu8.options[i].selected)
											filterarray.push(selectmenu8.options[i].value);
									}
								 	sorter.setfilter(10, filterarray, 1);
								}
							</script>
                            <br/>
                            <a href="javascript:void(0)" onclick="document.getElementById('list_C70').selectedIndex = -1; sorter.setfilter(10, []);">remove filter</a>
                        </td>
                        <td width="104px">
                        	<select multiple size=10 id="list_EB" style="width:100px;">
                            	<?php
									for ($i = 0; $i <= count($EB_list_string); $i++)
									{
										echo '<option value="'.$EB_list_num[$i].'">'.$EB_list_string[$i].'</option>';
									}
								?>
                            </select>
                            <script type="text/javascript">
								var selectmenu9 = document.getElementById("list_EB")
								selectmenu9.onchange=function()
								{
									var filterarray = [];
									for (var i = 0; i < selectmenu9.options.length; i++)
									{
										if (selectmenu9.options[i].selected)
											filterarray.push(selectmenu9.options[i].value);
									}
								 	sorter.setfilter(11, filterarray, 1);
								}
							</script>
                            <br/>
                            <a href="javascript:void(0)" onclick="document.getElementById('list_EB').selectedIndex = -1; sorter.setfilter(11, []);">remove filter</a>
                        </td>
                        <td width="84px" class="filter">
                        	<select multiple size=10 id="list_usp" style="width:80px;">
                            	<?php
									foreach ($usp_list as $this_usp)
									{
										echo '<option>'.$this_usp.'</option>';
									}
								?>
                            </select>
                            <script type="text/javascript">
								var selectmenu10 = document.getElementById("list_usp")
								selectmenu10.onchange=function()
								{
									var filterarray = [];
									for (var i = 0; i < selectmenu10.options.length; i++)
									{
										if (selectmenu10.options[i].selected)
											filterarray.push(selectmenu10.options[i].text);
									}
								 	sorter.setfilter(12, filterarray, 0);
								}
							</script>
                            <br/>
                            <a href="javascript:void(0)" onclick="document.getElementById('list_usp').selectedIndex = -1; sorter.setfilter(12, []);">remove filter</a>
                        </td>
                        <td width="84px">
                        	<select multiple size=10 id="list_phase" style="width:80px;">
                            	<?php
									foreach ($phase_list as $this_phase)
									{
										echo '<option>'.$this_phase.'</option>';
									}
								?>
                            </select>
                            <script type="text/javascript">
								var selectmenu11 = document.getElementById("list_phase")
								selectmenu11.onchange=function()
								{
									var filterarray = [];
									for (var i = 0; i < selectmenu11.options.length; i++)
									{
										if (selectmenu11.options[i].selected)
											filterarray.push(selectmenu11.options[i].text);
									}
								 	sorter.setfilter(13, filterarray, 0);
								}
							</script>
                            <br/>
                            <a href="javascript:void(0)" onclick="document.getElementById('list_phase').selectedIndex = -1; sorter.setfilter(13, []);">remove filter</a>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-right:10px;">
                    	<tr>
                        	<td class="text_main" style="text-align:left;">
                            	<p style="font-size:11px;">
                            	<a href="javascript:void(0)" onclick="document.getElementById('list_manufacturer').selectedIndex = -1; document.getElementById('list_type').selectedIndex = -1;document.getElementById('list_H').selectedIndex = -1;document.getElementById('list_S').selectedIndex = -1;document.getElementById('list_A').selectedIndex = -1;document.getElementById('list_B').selectedIndex = -1;document.getElementById('list_C28').selectedIndex = -1;document.getElementById('list_C70').selectedIndex = -1;document.getElementById('list_EB').selectedIndex = -1;document.getElementById('list_usp').selectedIndex = -1;document.getElementById('list_phase').selectedIndex = -1;sorter.setfilter(3, []);sorter.setfilter(4, []);sorter.setfilter(5, []);sorter.setfilter(6, []);sorter.setfilter(7, []);sorter.setfilter(8, []);sorter.setfilter(9, []);sorter.setfilter(10, []);sorter.setfilter(11, []);sorter.setfilter(12, []);sorter.setfilter(13, []);">remove all filters</a>
                            	</p>
                            </td>
                        </tr>
				</table>
                <table cellpadding="0" cellspacing="0" border="0" id="table" class="sortable">
                    <thead>
                        <tr>
                            <th><h3>ID</h3></th>
                            <th><h3><i>F<sub>s</sub></i></h3></th>
                            <th><h3>Name</h3></th>
                            <th><h3>Manufacturer</h3></th>
                            <th><h3>Silica type</h3></th>
                            <th><h3 style="font-family:'Times New Roman', Times, serif;"><b>H</b></h3></th>
                            <th><h3 style="font-family:'Times New Roman', Times, serif;"><b>S*</b></h3></th>
                            <th><h3 style="font-family:'Times New Roman', Times, serif;"><b>A</b></h3></th>
                            <th><h3 style="font-family:'Times New Roman', Times, serif;"><b>B</b></h3></th>
                            <th><h3><span style="font-family:'Times New Roman', Times, serif;"><b>C</b></span> (pH 2.8)</h3></th>
                            <th><h3><span style="font-family:'Times New Roman', Times, serif;"><b>C</b></span> (pH 7.0)</span></h3></th>
                            <th><h3>EB retention factor</h3></th>
                            <th><h3>USP type</h3></th>
                            <th><h3>Phase type</h3></th>
                        </tr>
                    </thead>
                    <tbody>
                    	<?php
						
							function check_string($string)
							{
								if ($string == '')
								{
									return '&nbsp;';
								}
								return $string;
							}
							
							foreach ($column_data as $this_column)
							{
								// Now create the table row
								echo '<tr>';
								echo '<td>'.check_string($this_column["id"]).'</td>';
								echo '<td>'.'&nbsp;'.'</td>';
								echo '<td>'.check_string($this_column["name"]).'</td>';
								echo '<td>'.check_string($this_column["manufacturer"]).'</td>';
								echo '<td>'.check_string($this_column["type"]).'</td>';
								echo '<td>'.check_string($this_column["H"]).'</td>';
								echo '<td>'.check_string($this_column["S*"]).'</td>';
								echo '<td>'.check_string($this_column["A"]).'</td>';
								echo '<td>'.check_string($this_column["B"]).'</td>';
								echo '<td>'.check_string($this_column["C2.8"]).'</td>';
								echo '<td>'.check_string($this_column["C7.0"]).'</td>';
								echo '<td>'.check_string($this_column["EB retention"]).'</td>';
								echo '<td>'.check_string($this_column["USP"]).'</td>';
								echo '<td>'.check_string($this_column["phase"]).'</td>';
								echo '</tr>';
							}
						?>
                    </tbody>
              </table>
              	<div id="controls">
		<div id="perpage">
			<select onchange="sorter.size(this.value)" id="optpagesize">
			<option value="5">5</option>
				<option value="10" selected="selected">10</option>
				<option value="20">20</option>
				<option value="50">50</option>
				<option value="100">100</option>
			</select>
			<span>Entries Per Page</span>
		</div>
		<div id="navigation">
			<img src="<?php echo $local_root;?>/tablesorter/images/first.gif" width="16" height="16" alt="First Page" onclick="sorter.move(-1,true)" />
			<img src="<?php echo $local_root;?>/tablesorter/images/previous.gif" width="16" height="16" alt="First Page" onclick="sorter.move(-1)" />
			<img src="<?php echo $local_root;?>/tablesorter/images/next.gif" width="16" height="16" alt="First Page" onclick="sorter.move(1)" />
			<img src="<?php echo $local_root;?>/tablesorter/images/last.gif" width="16" height="16" alt="Last Page" onclick="sorter.move(1,true)" />
		</div>
		<div id="text">Displaying Page <span id="currentpage"></span> of <span id="pagelimit"></span></div>
	</div>
	<script type="text/javascript" src="<?php echo 'http://' . $_SERVER['SERVER_NAME']; ?>/tablesorter/script.js"></script>
	<script type="text/javascript">
  var sorter = new TINY.table.sorter("sorter", document.getElementById("optpagesize").options[document.getElementById("optpagesize").selectedIndex].value);
	sorter.head = "head";
	sorter.asc = "asc";
	sorter.desc = "desc";
	sorter.even = "evenrow";
	sorter.odd = "oddrow";
	sorter.evensel = "evenselected";
	sorter.oddsel = "oddselected";
	sorter.paginate = true;
	sorter.currentid = "currentpage";
	sorter.limitid = "pagelimit";
	sorter.init("table", 1);
	var selectedColID = document.getElementById("list_allcolumns").options[document.getElementById("list_allcolumns").selectedIndex].value;
	sorter.calcNewFs(1, selectedColID, 0, 5, 6, 7, 8, 9);
  </script>

                <br />
                <br />
            </td>
        </tr>
    </table>
<?php
	include($_SERVER['DOCUMENT_ROOT'].'/template/footer.php');
?>