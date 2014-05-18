API DOCUMENTATION
=================
- - -
If you want to build an API client, here is how it works.

Let's assume you have a raspberry pi at 192.168.1.100.

You build a get request to http://192.168.1.100/gpio-web/api/v1/api.php, with the request parameters described in the next section. The API will always return a json object.
# API Request parameters #
- - -

## Param: action ##
When building the request, you <b>must</b> include the <b>action</b> parameter, which can be one of the following:
* [input](https://github.com/twinone/gpio-web/blob/master/APIDOC.md#action-input)
* [output](https://github.com/twinone/gpio-web/blob/master/APIDOC.md#action-output)
* [get_revision](https://github.com/twinone/gpio-web/blob/master/APIDOC.md#action-get_revision)
* [close](https://github.com/twinone/gpio-web/blob/master/APIDOC.md#action-close)

### Action: input ###
<i>Get the current state of one or more pins</i>

<b>requires param:</b> <u>pins</u> (see [below](https://github.com/twinone/gpio-web/blob/master/APIDOC.md#param-pins) how to specify this parameter)

<b>returns:</b> a json object with the current state of all selected pins

<b>example url:</b> http://192.168.1.100/gpio-web/api/v1/api.php?action=input&pins=17,2,3,4

<b>example response:</b>

``` json
{
    "status" : "ok",
    "pins" : {
        "17" : "1",
        "2" : "0",
        "3" : "1",
        "4" : "1"
    }
}
```

### Action: output ###
<i>Update the pins with either a high or low value</i>

<b>requires parameter:</b> ***value***: Either 1 (for high, the pin is activated) or 0 (for low, the pin is deactivated)

<b>returns:</b> default response ([*](https://github.com/twinone/gpio-web/blob/master/APIDOC.md#default-response))

<b>example url</b> (sets all pins to high): http://192.168.1.100/gpio-web/api/v1/api.php?action=output&pins=all&value=1

### Action: get_revision ###
<b>returns</b> either "rev1" or "rev2", according to the revision of the raspberry

<b>example url:</b> http://192.168.1.100/gpio-web/api/v1/api.php?action=get_revision

<b>example response:</b>

``` json
{
    "status" : "ok",
    "revision" : "rev2"
}
```

### Action: close ###
<i>This should be called when your client is done using the GPIO, so that the API can unexport the pins it has used</i>
<b>example url:</b> http://192.168.1.100/gpio-web/api/v1/api.php?action=close

- - -

## Param: pins ##
For all actions that require the <b>pins</b> parameter, you add it to the request.
You can specify pins in 3 ways:
* the word "all" <i>(all)</i>
* a single number, corresponding to the BCM GPIO pin scheme <i>(9)</i>
* a comma separated list of pin numbers, also as in the BCM GPIO pin scheme <i>(17,2,3,4)</i> (of course, the commas have to be url-encoded)

#

## Default Response ##
In addition to all the return values, in the response json there is always a key named <b>status</b> which will always take a value of <b>OK</b> or <b>ERROR</b>, when it is ERROR, there will be another key named <b>error</b>, which contains a description of the error that was produced.


For more information, you can read [the API source](https://github.com/twinone/gpio-web/blob/master/api/v1/api.php), it's easy to understand.



