(function (blocks, element, editor, components) {
  const { registerBlockType } = blocks;
  const { createElement, Fragment } = element;
  const { PanelBody, SelectControl, RangeControl, TextControl, Notice } =
    components;
  const { InspectorControls } = editor;

  registerBlockType("wetravel-trips/block", {
    title: "WeTravel Trips Block",
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
      wetravelUserID: {
        type: "string",
        default: "",
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
      itemsPerRow: {
        type: "number",
        default: 3,
      },
      itemsPerSlide: {
        type: "number",
        default: 3,
      },
      searchVisibility: {
        type: "boolean",
        default: false,
      },
      borderRadius: {
        type: "number",
        default: 6,
      },
      // Removed loadMoreText attribute as it's no longer needed
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
        itemsPerRow,
        itemsPerSlide,
        searchVisibility,
        borderRadius,
      } = attributes;

      // Set default values from PHP settings on first load only
      React.useEffect(() => {
        // Ensure settings exist
        const settings = window.wetravelTripsSettings || {};

        const updatedAttributes = {};

        if (!attributes.src && settings.src)
          updatedAttributes.src = settings.src;
        if (!attributes.slug && settings.slug)
          updatedAttributes.slug = settings.slug;
        if (attributes.env === "https://pre.wetravel.to" && settings.env)
          updatedAttributes.env = settings.env;
        if (!attributes.wetravelUserID && settings.wetravelUserID)
          updatedAttributes.wetravelUserID = settings.wetravelUserID;
        if (attributes.displayType === "vertical" && settings.displayType)
          updatedAttributes.displayType = settings.displayType;
        if (attributes.buttonType === "book_now" && settings.buttonType)
          updatedAttributes.buttonType = settings.buttonType;
        if (attributes.buttonColor === "#33ae3f" && settings.buttonColor)
          updatedAttributes.buttonColor = settings.buttonColor;
        if (attributes.itemsPerPage === 10 && settings.itemsPerPage)
          updatedAttributes.itemsPerPage = parseInt(settings.itemsPerPage);
        if (attributes.itemsPerRow === 3 && settings.itemsPerRow)
          updatedAttributes.itemsPerRow = parseInt(settings.itemsPerRow);
        if (attributes.itemsPerSlide === 3 && settings.itemsPerSlide)
          updatedAttributes.itemsPerSlide = parseInt(settings.itemsPerSlide);
        if (attributes.searchVisibility === false && settings.searchVisibility)
          updatedAttributes.searchVisibility = settings.searchVisibility;
        if (attributes.borderRadius === 6 && settings.borderRadius)
          updatedAttributes.borderRadius = parseInt(settings.borderRadius);

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
            searchVisibility: design.searchVisibility || searchVisibility,
          };

          setAttributes(designAttributes);
        }
      }, [designs, selectedDesignID]);

      // Generate pagination controls
      const renderPagination = () => {
        // Create a sample pagination UI
        const paginationStyle = {
          display: "flex",
          justifyContent: "center",
          alignItems: "center",
          margin: "20px 0",
          flexWrap: "wrap",
        };

        const pageItemStyle = {
          margin: "0 3px",
        };

        const pageLinkStyle = {
          display: "inline-block",
          padding: "8px 12px",
          color: "#333",
          border: "1px solid #ddd",
          borderRadius: "4px",
          textDecoration: "none",
          cursor: "pointer",
        };

        const activePageStyle = {
          ...pageLinkStyle,
          backgroundColor: buttonColor,
          color: "white",
          borderColor: buttonColor,
        };

        const disabledPageStyle = {
          ...pageLinkStyle,
          color: "#999",
          pointerEvents: "none",
          cursor: "default",
        };

        // Create sample pagination elements
        return createElement(
          "div",
          { style: paginationStyle, className: "wetravel-trips-pagination" },
          createElement(
            "div",
            { style: pageItemStyle, className: "page-item" },
            createElement(
              "span",
              { style: pageLinkStyle, className: "page-link" },
              "«"
            )
          ),
          createElement(
            "div",
            { style: pageItemStyle, className: "page-item" },
            createElement(
              "span",
              { style: pageLinkStyle, className: "page-link" },
              "‹"
            )
          ),
          createElement(
            "div",
            { style: pageItemStyle, className: "page-item active" },
            createElement(
              "span",
              { style: activePageStyle, className: "page-link" },
              "1"
            )
          ),
          createElement(
            "div",
            { style: pageItemStyle, className: "page-item" },
            createElement(
              "span",
              { style: pageLinkStyle, className: "page-link" },
              "2"
            )
          ),
          createElement(
            "div",
            { style: pageItemStyle, className: "page-item" },
            createElement(
              "span",
              { style: pageLinkStyle, className: "page-link" },
              "3"
            )
          ),
          createElement(
            "div",
            { style: pageItemStyle, className: "page-item disabled" },
            createElement(
              "span",
              { style: disabledPageStyle, className: "page-link" },
              "..."
            )
          ),
          createElement(
            "div",
            { style: pageItemStyle, className: "page-item" },
            createElement(
              "span",
              { style: pageLinkStyle, className: "page-link" },
              "10"
            )
          ),
          createElement(
            "div",
            { style: pageItemStyle, className: "page-item" },
            createElement(
              "span",
              { style: pageLinkStyle, className: "page-link" },
              "›"
            )
          ),
          createElement(
            "div",
            { style: pageItemStyle, className: "page-item" },
            createElement(
              "span",
              { style: pageLinkStyle, className: "page-link" },
              "»"
            )
          )
        );
      };

      // Generate preview based on display type
      const renderPreview = () => {
        // Get current design details
        const currentDesign =
          selectedDesignID && designs[selectedDesignID]
            ? designs[selectedDesignID]
            : { name: "Default" };

        // Get locations from the selected design
        const designLocations = currentDesign.locations || [];
        const tripLocation =
          designLocations.length > 0 ? designLocations[0] : "All Locations";

        // Preview container styles
        const containerStyle = {
          border: "1px dashed #ccc",
          padding: "20px",
          backgroundColor: "#f8f8f8",
        };

        // Apply CSS variables for button color
        const containerWithCSSVars = {
          ...containerStyle,
          "--button-color": buttonColor,
          "--border-radius": borderRadius + "px",
        };

        // Create search bar component
        const renderSearchBar = () => {
          const searchContainerStyle = {
            margin: "0 20px 20px 20px",
            display: "flex",
            gap: "10px",
            alignItems: "stretch",
            background: "#f8f9fa",
            borderRadius: "8px",
            padding: "16px",
            marginBottom: "20px",
          };

          const searchInputStyle = {
            flex: 1,
            padding: "12px 15px",
            borderRadius: "6px",
            border: "1px solid #cbd5e1",
            fontSize: "16px",
            outline: "none",
            height: "48px",
          };

          const searchButtonStyle = {
            ...buttonStyle,
            padding: "12px 20px",
            minWidth: "180px",
            height: "48px",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            borderRadius: "8px",
          };

          return createElement(
            "div",
            {
              className: "wetravel-search-container",
              style: searchContainerStyle,
            },
            createElement("input", {
              type: "text",
              placeholder: "Search trips by name...",
              style: searchInputStyle,
              className: "wetravel-search-input",
            }),
            createElement(
              "button",
              { style: searchButtonStyle, className: "wetravel-search-button" },
              "Select Location ▲"
            )
          );
        };

        // Create common button styles based on current button color
        const buttonStyle = {
          backgroundColor: buttonColor,
          color: "white",
          padding: "8px 16px",
          borderRadius: borderRadius + "px",
          display: "inline-block",
          cursor: "pointer",
          fontSize: "16px",
          border: `1px solid ${buttonColor}`,
          textDecoration: "none",
          fontWeight: 500,
          textAlign: "center",
        };

        const tripDescriptionStyle = {
          color: "#475569",
          maxHeight: "100px",
          overflow: "hidden",
          position: "relative",
          "&::after": {
            content: '""',
            position: "absolute",
            bottom: 0,
            left: 0,
            width: "100%",
            height: "30px",
            background:
              "linear-gradient(to bottom, rgba(255, 255, 255, 0), rgba(255, 255, 255, 1))",
            pointerEvents: "none",
          },
        };

        const tripDateOverlayStyle = {
          position: "absolute",
          bottom: "12px",
          left: "12px",
          backgroundColor: "#fff",
          color: "#64748b",
          padding: "4px 8px",
          borderRadius: "16px",
          fontSize: "14px",
          fontWeight: "500",
          lineHeight: "100%",
          letterSpacing: "0%",
          verticalAlign: "middle",
          zIndex: 2,
          border: "1px solid #cbd5e1",
          boxShadow: "0 2px 4px rgba(0, 0, 0, 0.1)",
        };

        // Button style for grid and carousel views
        const outlineButtonStyle = {
          ...buttonStyle,
          backgroundColor: "transparent",
          color: buttonColor,
        };

        // Create trip items
        const tripItems = [];
        for (let i = 0; i < 3; i++) {
          // Common trip elements
          const tripTitle = `Sample Trip ${i + 1}`;
          // Different descriptions for each trip
          const tripDescriptions = [
            "Experience the journey of a lifetime with our specially curated adventure package that combines exploration, relaxation, and cultural immersion. Discover hidden gems, taste local cuisine, and create unforgettable memories with expert guides leading the way through breathtaking landscapes and historic sites.",
            "Embark on an extraordinary expedition that takes you off the beaten path to discover authentic experiences. From sunrise yoga sessions to sunset beach dinners, every moment is crafted to provide the perfect balance of adventure and luxury in stunning natural surroundings.",
            "Join us for an immersive cultural journey that connects you with local communities and traditions. Learn traditional crafts, participate in cooking classes, and explore ancient ruins while staying in carefully selected accommodations that blend comfort with authentic local charm.",
          ];

          const tripDescription = tripDescriptions[i] || tripDescriptions[0];
          const tripDuration = "7 days";
          const tripDates = "Dec 15-22, 2024";
          const tripPrice = "$1,299";

          // Image placeholder styles
          const imagePlaceholderStyle = {
            backgroundColor: "#eee",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            color: "#888",
            borderRadius: "4px",
            position: "relative",
          };

          // Create trip item based on display type
          if (displayType === "vertical") {
            // Vertical trip item
            tripItems.push(
              createElement(
                "div",
                {
                  className: "trip-item",
                  style: {
                    display: "grid",
                    gridTemplateColumns: "2fr 6fr 1.2fr",
                    gap: "16px",
                    border: "1px solid #cbd5e1",
                    padding: "16px",
                    borderRadius: borderRadius + "px",
                    backgroundColor: "#fff",
                    boxShadow: "0px 4px 4px 0px rgba(174, 174, 174, 0.25)",
                    marginBottom: "16px",
                  },
                  key: i,
                },
                createElement(
                  "div",
                  {
                    className: "trip-image",
                    style: {
                      ...imagePlaceholderStyle,
                      minHeight: "200px",
                    },
                  },
                  "Trip Image"
                ),
                createElement(
                  "div",
                  {
                    className: "trip-content",
                    style: {
                      display: "flex",
                      flexDirection: "column",
                      justifyContent: "space-between",
                    },
                  },
                  createElement(
                    "div",
                    { className: "trip-title-desc" },
                    createElement(
                      "h3",
                      { style: { marginTop: 0, marginBottom: "8px" } },
                      tripTitle
                    ),
                    createElement(
                      "div",
                      {
                        className: "trip-description",
                        style: tripDescriptionStyle,
                      },
                      tripDescription
                    )
                  ),
                  createElement(
                    "div",
                    {
                      className: "trip-loc-duration",
                      style: {
                        display: "flex",
                        gap: "8px",
                        marginTop: "12px",
                        fontSize: "14px",
                      },
                    },
                    createElement(
                      "div",
                      {
                        className: "trip-tag",
                        style: {
                          borderRadius: "16px",
                          border: "1px solid #cbd5e1",
                          padding: "4px 8px",
                        },
                      },
                      tripDuration
                    ),
                    createElement(
                      "div",
                      {
                        className: "trip-tag",
                        style: {
                          borderRadius: "16px",
                          border: "1px solid #cbd5e1",
                          padding: "4px 8px",
                        },
                      },
                      tripLocation
                    )
                  )
                ),
                createElement(
                  "div",
                  {
                    className: "trip-price-button",
                    style: {
                      display: "flex",
                      flexDirection: "column",
                      justifyContent: "space-between",
                      height: "100%",
                    },
                  },
                  createElement(
                    "div",
                    { className: "trip-price", style: { textAlign: "right" } },
                    createElement(
                      "p",
                      { style: { margin: 0, color: "#64748b" } },
                      "From"
                    ),
                    createElement(
                      "span",
                      { style: { fontSize: "20px", fontWeight: 600 } },
                      tripPrice
                    )
                  ),
                  createElement(
                    "a",
                    {
                      className: "trip-button",
                      style: buttonStyle,
                      href: "#",
                    },
                    buttonText
                  )
                )
              )
            );
          } else if (displayType === "grid") {
            // Grid trip item
            tripItems.push(
              createElement(
                "div",
                {
                  className: "trip-item",
                  style: {
                    display: "flex",
                    flexDirection: "column",
                    gap: "16px",
                    border: "1px solid #cbd5e1",
                    padding: "16px",
                    borderRadius: borderRadius + "px",
                    backgroundColor: "#fff",
                    boxShadow: "0px 4px 4px 0px rgba(174, 174, 174, 0.25)",
                  },
                  key: i,
                },
                createElement(
                  "div",
                  {
                    className: "trip-image",
                    style: {
                      ...imagePlaceholderStyle,
                      height: "180px",
                    },
                  },
                  "Trip Image",
                  // Add date overlay for grid view
                  createElement(
                    "div",
                    {
                      className: "trip-date-overlay trip-tag",
                      style: tripDateOverlayStyle,
                    },
                    tripDates
                  )
                ),
                createElement(
                  "div",
                  {
                    className: "trip-content",
                    style: {
                      display: "flex",
                      flexDirection: "column",
                      gap: "16px",
                      flexGrow: 1,
                    },
                  },
                  createElement(
                    "div",
                    { className: "trip-title-desc" },
                    createElement(
                      "h3",
                      { style: { marginTop: 0, marginBottom: "8px" } },
                      tripTitle
                    ),
                    createElement(
                      "div",
                      {
                        className: "trip-description",
                        style: tripDescriptionStyle,
                      },
                      tripDescription
                    )
                  ),
                  createElement(
                    "div",
                    {
                      className: "trip-loc-duration",
                      style: {
                        display: "flex",
                        gap: "8px",
                        flexWrap: "wrap",
                        fontSize: "14px",
                      },
                    },
                    createElement(
                      "div",
                      {
                        className: "trip-tag",
                        style: {
                          borderRadius: "16px",
                          border: "1px solid #cbd5e1",
                          padding: "4px 8px",
                        },
                      },
                      tripLocation
                    )
                  ),
                  createElement(
                    "div",
                    {
                      className: "trip-price-button",
                      style: {
                        display: "flex",
                        flexDirection: "row",
                        justifyContent: "space-between",
                        alignItems: "flex-end",
                        marginTop: "auto",
                      },
                    },
                    createElement(
                      "div",
                      { className: "trip-price" },
                      createElement(
                        "p",
                        { style: { margin: 0, color: "#64748b" } },
                        "From"
                      ),
                      createElement(
                        "span",
                        { style: { fontSize: "20px", fontWeight: 600 } },
                        tripPrice
                      )
                    ),
                    createElement(
                      "a",
                      {
                        className: "trip-button",
                        style: outlineButtonStyle,
                        href: "#",
                      },
                      buttonText
                    )
                  )
                )
              )
            );
          } else if (displayType === "carousel") {
            // Carousel trip item
            tripItems.push(
              createElement(
                "div",
                {
                  className: "trip-item",
                  style: {
                    display: "flex",
                    flexDirection: "column",
                    gap: "16px",
                    border: "1px solid #cbd5e1",
                    padding: "16px",
                    borderRadius: borderRadius + "px",
                    backgroundColor: "#fff",
                    boxShadow: "0px 4px 4px 0px rgba(174, 174, 174, 0.25)",
                    width: "280px",
                    flexShrink: 1,
                  },
                  key: i,
                },
                createElement(
                  "div",
                  {
                    className: "trip-image",
                    style: {
                      ...imagePlaceholderStyle,
                      height: "180px",
                    },
                  },
                  "Trip Image",
                  // Add date overlay for carousel view
                  createElement(
                    "div",
                    {
                      className: "trip-date-overlay trip-tag",
                      style: tripDateOverlayStyle,
                    },
                    tripDates
                  )
                ),
                createElement(
                  "div",
                  {
                    className: "trip-content",
                    style: {
                      display: "flex",
                      flexDirection: "column",
                      gap: "16px",
                      flexGrow: 1,
                    },
                  },
                  createElement(
                    "div",
                    { className: "trip-title-desc" },
                    createElement(
                      "h3",
                      { style: { marginTop: 0, marginBottom: "8px" } },
                      tripTitle
                    ),
                    createElement(
                      "div",
                      {
                        className: "trip-description",
                        style: tripDescriptionStyle,
                      },
                      tripDescription
                    )
                  ),
                  createElement(
                    "div",
                    {
                      className: "trip-loc-price",
                      style: {
                        display: "flex",
                        justifyContent: "space-between",
                        alignItems: "center",
                      },
                    },
                    createElement(
                      "div",
                      {
                        className: "trip-loc-duration",
                        style: {
                          display: "flex",
                          gap: "8px",
                          flexWrap: "wrap",
                          fontSize: "14px",
                        },
                      },
                      createElement(
                        "div",
                        {
                          className: "trip-tag",
                          style: {
                            borderRadius: "16px",
                            border: "1px solid #cbd5e1",
                            padding: "4px 8px",
                          },
                        },
                        tripLocation
                      )
                    ),
                    createElement(
                      "div",
                      {
                        className: "trip-price-button",
                        style: {
                          marginTop: "12px",
                          direction: "rtl",
                        },
                      },
                      createElement(
                        "div",
                        { className: "trip-price" },
                        createElement(
                          "p",
                          { style: { margin: 0, color: "#64748b" } },
                          "From"
                        ),
                        createElement(
                          "span",
                          { style: { fontSize: "20px", fontWeight: 600 } },
                          tripPrice
                        )
                      )
                    )
                  )
                )
              )
            );
          }
        }

        // Render based on display type
        if (displayType === "grid") {
          // Calculate grid columns based on itemsPerRow
          const gridColumns = `repeat(${itemsPerRow}, 1fr)`;

          return createElement(
            Fragment,
            {},
            createElement(
              "div",
              { style: containerWithCSSVars },
              createElement(
                "h2",
                {},
                `Widget: ${currentDesign.name || "Default"} (Grid View)`
              ),
              searchVisibility && renderSearchBar(),
              createElement(
                "div",
                {
                  className: "wetravel-trips-container grid-view",
                  style: {
                    display: "grid",
                    gridTemplateColumns: gridColumns,
                    gap: "15px",
                    margin: "0px 20px",
                  },
                },
                ...tripItems
              ),
              displayType !== "carousel" && renderPagination()
            )
          );
        } else if (displayType === "vertical") {
          // Vertical view
          return createElement(
            Fragment,
            {},
            createElement(
              "div",
              { style: containerWithCSSVars },
              createElement(
                "h2",
                {},
                `Widget: ${currentDesign.name || "Default"} (Vertical View)`
              ),
              searchVisibility && renderSearchBar(),
              createElement(
                "div",
                {
                  className: "wetravel-trips-container vertical-view",
                  style: { margin: "0px 20px" },
                },
                ...tripItems
              ),
              displayType !== "carousel" && renderPagination()
            )
          );
        } else {
          // Carousel view (no search bar)
          return createElement(
            Fragment,
            {},
            createElement(
              "div",
              { style: containerWithCSSVars },
              createElement(
                "h2",
                {},
                `Widget: ${currentDesign.name || "Default"} (Carousel View)`
              ),
              createElement(
                "div",
                {
                  className: "wetravel-trips-container carousel-view",
                  style: { margin: "0px 20px" },
                },
                createElement(
                  "div",
                  {
                    className: "swiper",
                    style: {
                      padding: "0 60px",
                      position: "relative",
                      overflow: "visible",
                    },
                  },
                  createElement(
                    "div",
                    {
                      className: "swiper-wrapper",
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
                    {
                      className: "swiper-button-next",
                      style: {
                        position: "absolute",
                        top: "50%",
                        right: "10px",
                        transform: "translateY(-50%)",
                        width: "40px",
                        height: "40px",
                        backgroundColor: buttonColor,
                        borderRadius: "50%",
                        color: "#fff",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                      },
                    },
                    "›"
                  ),
                  createElement(
                    "div",
                    {
                      className: "swiper-button-prev",
                      style: {
                        position: "absolute",
                        top: "50%",
                        left: "10px",
                        transform: "translateY(-50%)",
                        width: "40px",
                        height: "40px",
                        backgroundColor: buttonColor,
                        borderRadius: "50%",
                        color: "#fff",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                      },
                    },
                    "‹"
                  )
                ),
                createElement(
                  "div",
                  {
                    className: "swiper-pagination",
                    style: {
                      textAlign: "center",
                      margin: "20px 0",
                      display: "flex",
                      justifyContent: "center",
                      gap: "5px",
                    },
                  },
                  createElement("span", {
                    style: {
                      width: "12px",
                      height: "12px",
                      backgroundColor: buttonColor,
                      borderRadius: "50%",
                      display: "inline-block",
                    },
                  }),
                  createElement("span", {
                    style: {
                      width: "12px",
                      height: "12px",
                      backgroundColor: "#ddd",
                      borderRadius: "50%",
                      display: "inline-block",
                    },
                  }),
                  createElement("span", {
                    style: {
                      width: "12px",
                      height: "12px",
                      backgroundColor: "#ddd",
                      borderRadius: "50%",
                      display: "inline-block",
                    },
                  })
                )
              )
            )
          );
        }
      };
      // Create options for design dropdown
      const designOptions = [{ label: "Choose a widget...", value: "" }];

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
            { title: "Widget Library", initialOpen: true },
            createElement(SelectControl, {
              label: "Select Widget",
              value: selectedDesignID,
              options: designOptions,
              onChange: (value) => setAttributes({ selectedDesignID: value }),
              help: "Select a widget from your Widget Library",
            })
          ),
          createElement(
            PanelBody,
            { title: "Pagination Settings", initialOpen: true },
            attributes.displayType === "grid" &&
              createElement(RangeControl, {
                label: "Items Per Row",
                value: itemsPerRow,
                onChange: (value) => setAttributes({ itemsPerRow: value }),
                min: 1,
                max: 4,
              }),
            attributes.displayType !== "carousel" &&
              createElement(RangeControl, {
                label: "Items Per Page",
                value: itemsPerPage,
                onChange: (value) => setAttributes({ itemsPerPage: value }),
                min: 1,
                max: 50,
              }),
            attributes.displayType === "carousel" &&
              createElement(RangeControl, {
                label: "Items Per Slide",
                value: itemsPerSlide,
                onChange: (value) => setAttributes({ itemsPerSlide: value }),
                min: 1,
                max: 4,
              }),
            createElement(RangeControl, {
              label: "Border Radius",
              value: attributes.borderRadius,
              onChange: (value) => setAttributes({ borderRadius: value }),
              min: 1,
              max: 100,
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
