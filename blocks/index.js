(function (blocks, element, editor, components) {
  const { registerBlockType } = blocks;
  const { createElement, Fragment } = element;
  const {
    PanelBody,
    SelectControl,
    ColorPicker,
    TextControl,
    RangeControl,
    Button,
    ComboboxControl,
    DatePicker,
  } = components;
  const { InspectorControls } = editor;

  registerBlockType("all-trips/block", {
    title: "All Trips Block",
    icon: "location-alt",
    category: "widgets",
    attributes: {
      src: {
        type: "string",
        default: "",
      },
      slug: {
        type: "string",
        default: "",
      },
      env: {
        type: "string",
        default: "https://pre.wetravel.to",
      },
      displayType: {
        type: "string",
        default: "vertical",
      },
      buttonText: {
        type: "string",
        default: "Book Now",
      },
      buttonType: {
        type: "string",
        default: "book_now",
      },
      buttonColor: {
        type: "string",
        default: "#33ae3f",
      },
      itemsPerPage: {
        type: "number",
        default: 10,
      },
      loadMoreText: {
        type: "string",
        default: "Load More",
      },
      tripType: {
        type: "number",
        default: 0,
      },
      dateRange: {
        type: "string",
        default: "",
      },
    },

    edit: function (props) {
      const { attributes, setAttributes } = props;
      const settings = window.allTripsSettings || {};

      // Set default values from PHP settings on first load only
      // In the edit function, modify useEffect like this
      React.useEffect(() => {
        // Only update attributes that are still at their default values
        const updatedAttributes = {};

        if (!attributes.src && settings.src)
          updatedAttributes.src = settings.src;
        if (!attributes.slug && settings.slug)
          updatedAttributes.slug = settings.slug;
        if (attributes.env === "https://pre.wetravel.to" && settings.env)
          updatedAttributes.env = settings.env;
        if (attributes.displayType === "vertical" && settings.displayType)
          updatedAttributes.displayType = settings.displayType;
        if (attributes.buttonType === "book_now" && settings.buttonType)
          updatedAttributes.buttonType = settings.buttonType;
        if (attributes.buttonColor === "#33ae3f" && settings.buttonColor)
          updatedAttributes.buttonColor = settings.buttonColor;
        if (attributes.itemsPerPage === 10 && settings.itemsPerPage)
          updatedAttributes.itemsPerPage = parseInt(settings.itemsPerPage);
        if (attributes.loadMoreText === "Load More" && settings.loadMoreText)
          updatedAttributes.loadMoreText = settings.loadMoreText;

        // Set default buttonText based on buttonType if not already set
        if (!attributes.buttonText) {
          updatedAttributes.buttonText =
            attributes.buttonType === "book_now" ? "Book Now" : "View Trip";
        }

        // Only update if there are changes
        if (Object.keys(updatedAttributes).length > 0) {
          setAttributes(updatedAttributes);
        }
      }, []);

      // Also add an effect to update buttonText when buttonType changes
      React.useEffect(() => {
        // Only update buttonText if it's still the default value
        if (
          attributes.buttonText === "Book Now" ||
          attributes.buttonText === "View Trip"
        ) {
          setAttributes({
            buttonText:
              attributes.buttonType === "book_now" ? "Book Now" : "View Trip",
          });
        }
      }, [attributes.buttonType]);

      // Generate preview based on display type
      const renderPreview = () => {
        const { displayType, buttonText, buttonType, buttonColor } = attributes;

        // Preview styles
        const containerStyle = {
          border: "1px dashed #ccc",
          padding: "20px",
          backgroundColor: "#f8f8f8",
        };

        const tripStyle = {
          border: "1px solid #ddd",
          padding: "15px",
          marginBottom: "10px",
          borderRadius: "4px",
          backgroundColor: "white",
        };

        const buttonStyle = {
          backgroundColor: buttonColor,
          color: "white",
          padding: "8px 16px",
          borderRadius: "4px",
          display: "inline-block",
          cursor: "pointer",
        };

        const loadMoreStyle = {
          backgroundColor: buttonColor,
          color: "white",
          padding: "8px 16px",
          borderRadius: "4px",
          display: attributes.displayType !== "carousel" ? "block" : "none",
          width: "200px",
          margin: "20px auto",
          textAlign: "center",
        };

        // Create trip items
        const tripItems = [];
        for (let i = 0; i < 3; i++) {
          tripItems.push(
            createElement(
              "div",
              { style: tripStyle, key: i },
              createElement(
                "div",
                {
                  style: {
                    height: "150px",
                    backgroundColor: "#eee",
                    marginBottom: "10px",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    color: "#888",
                  },
                },
                "Trip Image"
              ),
              createElement("h3", {}, `Sample Trip ${i + 1}`),
              createElement(
                "p",
                {},
                "This is a preview of how your trips will appear."
              ),
              createElement("span", { style: buttonStyle }, buttonText)
            )
          );
        }

        // Render based on display type
        if (displayType === "grid") {
          const gridStyle = {
            display: "grid",
            gridTemplateColumns: "repeat(auto-fill, minmax(250px, 1fr))",
            gap: "15px",
          };

          return createElement(
            Fragment,
            {},
            createElement(
              "div",
              { style: containerStyle },
              createElement("h2", {}, "Grid View Preview"),
              createElement("div", { style: gridStyle }, ...tripItems),
              createElement(
                "div",
                { style: loadMoreStyle },
                attributes.loadMoreText
              )
            )
          );
        } else if (displayType === "carousel") {
          return createElement(
            Fragment,
            {},
            createElement(
              "div",
              { style: containerStyle },
              createElement("h2", {}, "Carousel View Preview"),
              createElement(
                "div",
                {
                  style: {
                    display: "flex",
                    overflowX: "auto",
                    gap: "15px",
                    padding: "10px 0",
                  },
                },
                ...tripItems
              ),
              createElement(
                "div",
                { style: { textAlign: "center", margin: "10px 0" } },
                "• • •"
              )
            )
          );
        } else {
          // Vertical view (default)
          return createElement(
            Fragment,
            {},
            createElement(
              "div",
              { style: containerStyle },
              createElement("h2", {}, "Vertical View Preview"),
              createElement("div", {}, ...tripItems),
              createElement(
                "div",
                { style: loadMoreStyle },
                attributes.loadMoreText
              )
            )
          );
        }
      };

      return createElement(
        Fragment,
        {},
        // Block Preview
        renderPreview(),

        // Sidebar Controls
        createElement(
          InspectorControls,
          {},
          createElement(
            PanelBody,
            { title: "Display Options", initialOpen: true },
            createElement(SelectControl, {
              label: "Trip Display Type",
              value: attributes.displayType,
              options: [
                { label: "Vertical", value: "vertical" },
                { label: "Carousel", value: "carousel" },
                { label: "Grid", value: "grid" },
              ],
              onChange: (value) => setAttributes({ displayType: value }),
            }),
            createElement(TextControl, {
              label: "Button Text",
              value: attributes.buttonText,
              onChange: (value) => setAttributes({ buttonText: value }),
            }),
            createElement(SelectControl, {
              label: "Button Type",
              value: attributes.buttonType,
              options: [
                { label: "Book Now", value: "book_now" },
                { label: "Trip Link", value: "trip_link" },
              ],
              onChange: (value) => setAttributes({ buttonType: value }),
            }),
            createElement("label", {}, "Button Color"),
            createElement(ColorPicker, {
              color: attributes.buttonColor,
              onChangeComplete: (value) =>
                setAttributes({ buttonColor: value.hex }),
            })
          ),
          createElement(
            PanelBody,
            { title: "Pagination Settings", initialOpen: true },
            attributes.displayType !== "carousel" &&
              createElement(RangeControl, {
                label: "Items Per Page",
                value: attributes.itemsPerPage,
                onChange: (value) => setAttributes({ itemsPerPage: value }),
                min: 1,
                max: 50,
              }),
            attributes.displayType !== "carousel" &&
              createElement(TextControl, {
                label: "Load More Button Text",
                value: attributes.loadMoreText,
                onChange: (value) => setAttributes({ loadMoreText: value }),
              })
          ),
          createElement(
            PanelBody,
            { title: "Filter Settings", initialOpen: true },
            createElement(ComboboxControl, {
              label: "Trip Type",
              value: attributes.tripType,
              onChange: (value) => setAttributes({ tripType: value }),
              options: [
                { label: "Recurring Trips", value: 0 },
                { label: "One Time Trips", value: 1 },
              ],
            }),
            createElement(DatePicker, {
              label: "Date Range",
              value: attributes.dateRange,
              onChange: (value) => setAttributes({ dateRange: value }),
            })
          ),
          createElement(
            PanelBody,
            { title: "WeTravel Settings", initialOpen: true },
            createElement(TextControl, {
              label: "Embed Slug",
              value: attributes.slug,
              onChange: (value) => setAttributes({ slug: value }),
              help: "Enter the WeTravel slug for your trips",
            }),
            createElement(TextControl, {
              label: "Embed Environment",
              value: attributes.env,
              onChange: (value) => setAttributes({ env: value }),
              help: "Enter the WeTravel env for your trips",
            }),
            createElement(TextControl, {
              label: "Embed SRC",
              value: attributes.src,
              onChange: (value) => setAttributes({ src: value }),
              help: "Enter the WeTravel env for your trips",
            })
          )
        )
      );
    },

    save: function () {
      // Dynamic block - rendered serverside
      return null;
    },
  });
})(window.wp.blocks, window.wp.element, window.wp.editor, window.wp.components);
