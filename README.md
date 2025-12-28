# Cloud Recruitment
> You are free to choose plain PHP or a framework as long as you are able to explain your choice.

Create one or multiple PHP programs that examine the given logfile provide the following insights:
    
1. What are the **10 license serial numbers** that try to access the server the most?
	- How many times are they trying to access the server?

2. One license serial number should only be active on one physical device.   
	- Describe how you identify a single device as such. 
	- Provide a way to identify licenses that are installed on more than one device. 
	- What are the 10 license serials that violoate this rule the most?
	- On how many distinct devices are these licenses installed?

3. **Bonus**: Based on the information given in the **specs** metadata, try to identify the different classes of **hardware** that are in use and provide the **number of licenses** that are active on these types of hardware.

## Fields
| **field** | **value** |
| ------------ | ------------ |
| **Public IP** | 71.215.xxx.xxx |
| **Update Server** | update-001.v12.xxx.xxxx.de |
| **Date/Time** | [2023-02-26T00:00:09+01:00] |
| **Protocol** | GET /8700d5be36cf5cec542fac3efbcddfd45aa3a114/do-update.sh HTTP/1.0" |
| **Status** | 200 |
| **Size** | 2427 |
| **Proxy** | proxy-xxx |
| **RT** | 0.028 |
| **Serial** | 59422F93... |
| **Version** | 12.x.x |
| **Specs** | 4sIAAAAAAAAEzWOT0vEMBDF... |
| **not_after** | not-set |
| **remaining_days** | not-set |

## Specs
| **field** | **value** | **description** |
| ------------ | ------------ | ------------ |
| **mac** | 8f;e5;5d;xx;xx;xx" | The MAC-Address of the first NIC of the UTM. | 
| **architecture** | 64 | 32/64 |
| **machine** | x86_64 | More detailed architecture description. |
| **mem** | 8011088kB | The amount of installed RAM. |
| **cpu** | Intel(R) Celeron(R) J6412 @ 2.00GHz | The installed CPU model. |
| **disk_root** | 1021984 | The size of the root disk in kilobytes. |
| **disk_data** | 60215904 | The size of the data disk in kilobytes. |
| **uptime** | 02:19:58 | The uptime of the appliance **[dd;hh;mm]**. |
| **fwversion ** | 12.x.x | The installed firmware version. |
| **l2tp** | DOWN | Status of the L2TP service **[DOWN/ERROR/UP]**. |
| **qos** | 0 | The number of configured QoS rules. |
| **httpaveng** | clamd | The type of AV engine that is active in the HTTP proxy **[unknown/csamd/clamav]** |
| **spcf** | 1 | The status of the contentfilter plugin in the HTTP proxy **[0/1]**. |
