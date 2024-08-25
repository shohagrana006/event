// css & scss

// js

$(document).ready(function () {
  // Cart quantity check before saving the current cart element
  $("#add-to-cart-button").click(function (e) {
    console.log("Clicked");
    callEvent().then((e) => {
      console.log("called");
      //   var $ticketsQte = 0;
      //   $(".eventdate-ticket-qte").each(function () {
      //     if ($(this).val()) {
      //       $ticketsQte += parseInt($(this).val());
      //     }
      //   });
      //   if ($ticketsQte == 0) {
      //     showStackBarTop(
      //       "error",
      //       "",
      //       Translator.trans(
      //         "Please select the tickets quantity you want to buy",
      //         {},
      //         "javascript"
      //       )
      //     );
      //   } else {
      //     $("#add-to-cart-form").submit();
      //   }
    });
  });

  async function callEvent(params) {
    try {
      // Load the external JS file
      await fetch(tracardiJsUrl)
        .then((response) => {
          if (!response.ok) {
            throw new Error("Network response was not ok");
          }
          return response.text();
        })
        .then((scriptContent) => {
          const scriptElement = document.createElement("script");
          scriptElement.text = scriptContent;
          document.body.appendChild(scriptElement);
        });

      // Track the event
      await new Promise((resolve, reject) => {
        try {
          window.tracker.track("Close the ticket purchase window", {
            Type: "Click",
            Action: "Attendee",
          });

          resolve();
        } catch (error) {
          reject(error);
        }
      });
    } catch (error) {
      console.error("Error:", error);
    }
  }
});
