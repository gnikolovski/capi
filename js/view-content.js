/**
 * @file
 * Meta Conversions API - ViewContent event handling.
 */

(function (drupalSettings) {

  'use strict';

  setTimeout(() => {
    fetch(drupalSettings.capi.viewContent.url, {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(drupalSettings.capi.viewContent.data),
      credentials: 'same-origin'
    })
      .then(response => {
        if (!response.ok) {
          throw new Error('Meta Conversions API: Failed to receive a valid response from the network.');
        }
        return response.json();
      })
      .then(data => {
        console.log('Meta Conversions API. Success: ', data);
      })
      .catch(error => {
        console.error('Meta Conversions API. Error: ', error);
      });
  }, 2500);

})(drupalSettings);
