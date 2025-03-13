(function (blocks, element, editor, components) {
  const { registerBlockType } = blocks;
  const { createElement, Fragment } = element;
  const { PanelBody, SelectControl, RangeControl, TextControl, Notice } =
    components;
  const { InspectorControls } = editor;

  registerBlockType("all-trips/block", {
    title: "All Trips Block",
    icon: "location-alt",
    category: "widgets",
    attributes: {
      designs: {
        type: "array",
        default: [],
      },
      selectedDesignID: {
        type: "string",
        default: "",
      },
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
      buttonType: {
        type: "string",
        default: "book_now",
      },
      buttonText: {
        type: "string",
        default: "Book Now",
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
    },

    edit: function (props) {
      // Debug log - add to the edit function
      const { attributes, setAttributes } = props;
      const {
        selectedDesignID,
        designs,
        displayType,
        buttonText,
        buttonColor,
        itemsPerPage,
        loadMoreText,
      } = attributes;

      // Set default values from PHP settings on first load only
      React.useEffect(() => {
        // Ensure settings exist
        const settings = window.allTripsSettings || {};

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

        // Properly load designs
        if (settings.designs) {
          // Make sure designs is properly processed into a consistent format
          updatedAttributes.designs = settings.designs;
        }

        // Only update if there are changes
        if (Object.keys(updatedAttributes).length > 0) {
          setAttributes(updatedAttributes);
        }
      }, []);

      // Track when designs become available
      React.useEffect(() => {
        // This will run whenever designs changes
        if (designs && selectedDesignID && designs[selectedDesignID]) {
          const design = designs[selectedDesignID];

          // Apply design settings to block attributes
          const designAttributes = {
            displayType: design.displayType || displayType,
            buttonColor: design.buttonColor || buttonColor,
            buttonType: design.buttonType || attributes.buttonType,
            buttonText: design.buttonText || buttonText,
            tripType: design.tripType || "all",
          };

          setAttributes(designAttributes);
        }
      }, [designs, selectedDesignID]);

      // Generate preview based on display type
      const renderPreview = () => {
        console.log(selectedDesignID);
        console.log(designs);
        // Get current design details
        const currentDesign =
          selectedDesignID && designs[selectedDesignID]
            ? designs[selectedDesignID]
            : { name: "Default" };

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
          ...(displayType === "vertical" && {
            display: "grid",
            gap: "15px",
            gridTemplateColumns: "3fr 4fr 2fr",
          }),
        };

        const buttonStyle = {
          backgroundColor: buttonColor,
          color: "white",
          padding: "8px 16px",
          borderRadius: "4px",
          display: "inline-block",
          cursor: "pointer",
          fontSize: "16px",
        };

        const loadMoreStyle = {
          backgroundColor: buttonColor,
          color: "white",
          padding: "8px 16px",
          borderRadius: "4px",
          display: displayType !== "carousel" ? "block" : "none",
          width: "200px",
          margin: "20px auto",
          textAlign: "center",
        };

        const pStyle = {
          color: "#888",
          fontSize: "16px",
          margin: "0",
        };

        const spanStyle = {
          fontSize: "16px",
          fontWeight: "bold",
          color: "#333",
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
                    backgroundColor: "#eee",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    color: "#888",
                    ...(displayType !== "vertical" && {
                      height: "150px",
                      marginBottom: "10px",
                    }),
                  },
                },
                "Trip Image"
              ),
              createElement(
                "div",
                {},
                createElement("h3", {}, `Sample Trip ${i + 1}`),
                createElement(
                  "p",
                  { style: pStyle },
                  "About your trip description goes here."
                ),
                createElement(
                  "span",
                  { style: spanStyle },
                  currentDesign.tripType === "one-time" ? "Dates" : "Duration"
                )
              ),
              createElement(
                "div",
                {},
                createElement(
                  "p",
                  { style: { fontSize: "16px", fontWeight: "bold" } },
                  "From $1,000"
                ),
                createElement("span", { style: buttonStyle }, buttonText)
              )
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
              createElement(
                "h2",
                {},
                `Design: ${currentDesign.name || "Default"} (Grid View)`
              ),
              createElement("div", { style: gridStyle }, ...tripItems),
              createElement("div", { style: loadMoreStyle }, loadMoreText)
            )
          );
        } else if (displayType === "carousel") {
          return createElement(
            Fragment,
            {},
            createElement(
              "div",
              { style: containerStyle },
              createElement(
                "h2",
                {},
                `Design: ${currentDesign.name || "Default"} (Carousel View)`
              ),
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
              createElement(
                "h2",
                {},
                `Design: ${currentDesign.name || "Default"} (Vertical View)`
              ),
              createElement("div", {}, ...tripItems),
              createElement("div", { style: loadMoreStyle }, loadMoreText)
            )
          );
        }
      };

      // Create options for design dropdown
      const designOptions = [{ label: "Choose a design...", value: "" }];

      // Add designs from either array or object format
      if (Array.isArray(designs)) {
        designs.forEach((design) => {
          designOptions.push({
            label: design.name,
            value: design.id,
          });
        });
      } else {
        Object.keys(designs).forEach((key) => {
          designOptions.push({
            label: designs[key].name,
            value: key,
          });
        });
      }

      return createElement(
        Fragment,
        {},
        // Block Preview
        renderPreview(),

        // Sidebar Controls - Simplified
        createElement(
          InspectorControls,
          {},
          createElement(
            PanelBody,
            { title: "Design Library", initialOpen: true },
            createElement(SelectControl, {
              label: "Select Design",
              value: selectedDesignID,
              options: designOptions,
              onChange: (value) => setAttributes({ selectedDesignID: value }),
              help: "Select a design from your Design Library",
            })
          ),
          createElement(
            PanelBody,
            { title: "Pagination Settings", initialOpen: true },
            attributes.displayType !== "carousel" &&
              createElement(RangeControl, {
                label: "Items Per Page",
                value: itemsPerPage,
                onChange: (value) => setAttributes({ itemsPerPage: value }),
                min: 1,
                max: 50,
              }),
            attributes.displayType !== "carousel" &&
              createElement(TextControl, {
                label: "Load More Button Text",
                value: loadMoreText,
                onChange: (value) => setAttributes({ loadMoreText: value }),
              }),
            attributes.displayType === "carousel" &&
              createElement(Notice, {
                status: "warning",
                children: "Carousel view does not support pagination.",
                isDismissible: false,
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
