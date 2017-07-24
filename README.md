# SmartLabel RESTful API

**SmartLabel** RESTful API is the simplest and most efficient way to order and resell labels on a roll.

  1. Create a form with the the parameters and behaviour described in the `GET /parameters` route (could be web or app)
  2. Send the request to your webserver, preferably using AJAX
  3. Call the `POST /quote` route to create a new quote and retreive production details *(purchase price, lead time, reel diameter, weight)*. The request can safely be stored in your own caching system.
  4. Apply your margin and return the values to your form
  5. When your customer did place his order, send a `POST` request to the `/order` route with the quote identifier and your own order ID.
  6. Track production and shipping of all your pending orders by sending a `GET` request to the `/status` route

**This API is intended to be called and MUST be proxied by your webserver. It will not work directly through AJAX.**

## `GET /parameters`

The `/parameters` route returns in a single HTTP request you can safely cache, a JSON object containing every values required to build a complete front-end form.

---

The form is generally composed of the following fields

* a checkbox / radio to choose between **automatic and manual application**
* a dropdown to select the **material**
* a dropdown to select the **finish** (based on the  selected material)
* two numeric input fields, for **width** and **height** *(in centimeters)*
* a dropdown / radio to choose between **cut to shape** and **straight cut** *(in case of cut to shape, you must ask your customer for a 100% black vector path)*

The following fields apply only when automatic application is selected

* a dropdown / radio to choose between **core sizes**
* a numeric input, for the **number of labels per reel** *(used to calculate the estimated reel diameter)*
* dropdown, or button, to switch the **orientation** of the graphic file on the label
*(in degrees, one of 0, 90, 180 and 270)*

We can print multiple versions of the same specs, but you're limited to 10 different visuals per order. You can create a single version form, with a text input and a file input, then allow your customer to duplicate this form, with a \"add an additional version\" button. You may also choose to provide ten slots with drag'n drop capabilities.


## `POST /quote`

Create a new quote

### Parameters

| Parameter name | Type | Description |
| ---            | ---  | ---         |
| scenario       |  integer | the scenario id for the selected material and finish
| quantity      | integer | the total number of individual labels
| height        | number | height of a label, in cm
| width         | number | width of a label, in cm
| application | string | `"automatic"` or `"manual"` : type of application, if `"automatic"`, the reels are intended to be applied with a machine
| core_size | number | the size of the core, in millimeters
| nb_labels_per_reel | integer | the number of label on one reel
| nb_reels | integer | total number of reels
| orientation | integer | orientation of the visual on the reel : 0, 90, 180 or 270
| nb_labels_per_versions | array[number] | number of label for each version