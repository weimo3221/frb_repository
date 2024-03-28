<? echo "<\?xml version=\"1.0\" encoding=\"utf-8\"?\>"; ?>
<scpd xmlns="urn:schemas-upnp-org:service-1-0">
   <specVersion>
      <major>1</major>
      <minor>0</minor>
   </specVersion>
   <actionList>
      <action>
         <name>DelAPSettings</name>
         <argumentList>
            <argument>
               <name>NewAPSettings</name>
               <direction>in</direction>
               <relatedStateVariable>APSettings</relatedStateVariable>
            </argument>
         </argumentList>
      </action>
      <action>
         <name>DelSTASettings</name>
         <argumentList>
            <argument>
               <name>NewSTASettings</name>
               <direction>in</direction>
               <relatedStateVariable>STASettings</relatedStateVariable>
            </argument>
         </argumentList>
      </action>
      <action>
         <name>GetAPSettings</name>
         <argumentList>
            <argument>
               <name>NewMessage</name>
               <direction>in</direction>
               <relatedStateVariable>Message</relatedStateVariable>
            </argument>
            <argument>
               <name>NewAPSettings</name>
               <direction>out</direction>
               <relatedStateVariable>APSettings</relatedStateVariable>
            </argument>
         </argumentList>
      </action>
      <action>
         <name>GetDeviceInfo</name>
         <argumentList>
            <argument>
               <name>NewDeviceInfo</name>
               <direction>out</direction>
               <relatedStateVariable>DeviceInfo</relatedStateVariable>
            </argument>
         </argumentList>
      </action>
      <action>
         <name>GetSTASettings</name>
         <argumentList>
            <argument>
               <name>NewMessage</name>
               <direction>in</direction>
               <relatedStateVariable>Message</relatedStateVariable>
            </argument>
            <argument>
               <name>NewSTASettings</name>
               <direction>out</direction>
               <relatedStateVariable>STASettings</relatedStateVariable>
            </argument>
         </argumentList>
      </action>
      <action>
         <name>PutMessage</name>
         <argumentList>
            <argument>
               <name>NewInMessage</name>
               <direction>in</direction>
               <relatedStateVariable>InMessage</relatedStateVariable>
            </argument>
            <argument>
               <name>NewOutMessage</name>
               <direction>out</direction>
               <relatedStateVariable>OutMessage</relatedStateVariable>
            </argument>
         </argumentList>
      </action>
      <action>
         <name>PutWLANResponse</name>
         <argumentList>
            <argument>
               <name>NewMessage</name>
               <direction>in</direction>
               <relatedStateVariable>Message</relatedStateVariable>
            </argument>
            <argument>
               <name>NewWLANEventType</name>
               <direction>in</direction>
               <relatedStateVariable>WLANEventType</relatedStateVariable>
            </argument>
            <argument>
               <name>NewWLANEventMAC</name>
               <direction>in</direction>
               <relatedStateVariable>WLANEventMAC</relatedStateVariable>
            </argument>
         </argumentList>
      </action>
      <action>
         <name>RebootAP</name>
         <argumentList>
            <argument>
               <name>NewAPSettings</name>
               <direction>in</direction>
               <relatedStateVariable>APSettings</relatedStateVariable>
            </argument>
         </argumentList>
      </action>
      <action>
         <name>RebootSTA</name>
         <argumentList>
            <argument>
               <name>NewSTASettings</name>
               <direction>in</direction>
               <relatedStateVariable>STASettings</relatedStateVariable>
            </argument>
         </argumentList>
      </action>
      <action>
         <name>ResetAP</name>
         <argumentList>
            <argument>
               <name>NewMessage</name>
               <direction>in</direction>
               <relatedStateVariable>Message</relatedStateVariable>
            </argument>
         </argumentList>
      </action>
      <action>
         <name>ResetSTA</name>
         <argumentList>
            <argument>
               <name>NewMessage</name>
               <direction>in</direction>
               <relatedStateVariable>Message</relatedStateVariable>
            </argument>
         </argumentList>
      </action>
      <action>
         <name>SetAPSettings</name>
         <argumentList>
            <argument>
               <name>NewAPSettings</name>
               <direction>in</direction>
               <relatedStateVariable>APSettings</relatedStateVariable>
            </argument>
         </argumentList>
      </action>
      <action>
         <name>SetSelectedRegistrar</name>
         <argumentList>
            <argument>
               <name>NewMessage</name>
               <direction>in</direction>
               <relatedStateVariable>Message</relatedStateVariable>
            </argument>
         </argumentList>
      </action>
      <action>
         <name>SetSTASettings</name>
         <argumentList>
            <argument>
               <name>NewSTASettings</name>
               <direction>in</direction>
               <relatedStateVariable>STASettings</relatedStateVariable>
            </argument>
         </argumentList>
      </action>
   </actionList>
   <serviceStateTable>
      <stateVariable sendEvents="no">
         <name>WLANEventMAC</name>
         <dataType>string</dataType>
      </stateVariable>
      <stateVariable sendEvents="yes">
         <name>APStatus</name>
         <dataType>ui1</dataType>
         <defaultValue>0</defaultValue>
      </stateVariable>
      <stateVariable sendEvents="no">
         <name>Message</name>
         <dataType>bin.base64</dataType>
      </stateVariable>
      <stateVariable sendEvents="no">
         <name>WLANEventType</name>
         <dataType>ui1</dataType>
      </stateVariable>
      <stateVariable sendEvents="no">
         <name>APSettings</name>
         <dataType>bin.base64</dataType>
      </stateVariable>
      <stateVariable sendEvents="no">
         <name>OutMessage</name>
         <dataType>bin.base64</dataType>
      </stateVariable>
      <stateVariable sendEvents="yes">
         <name>STAStatus</name>
         <dataType>ui1</dataType>
         <defaultValue>0</defaultValue>
      </stateVariable>
      <stateVariable sendEvents="yes">
         <name>WLANEvent</name>
         <dataType>bin.base64</dataType>
      </stateVariable>
      <stateVariable sendEvents="no">
         <name>DeviceInfo</name>
         <dataType>bin.base64</dataType>
      </stateVariable>
      <stateVariable sendEvents="no">
         <name>STASettings</name>
         <dataType>bin.base64</dataType>
      </stateVariable>
      <stateVariable sendEvents="no">
         <name>InMessage</name>
         <dataType>bin.base64</dataType>
      </stateVariable>
   </serviceStateTable>
</scpd>
